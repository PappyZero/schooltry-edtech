<?php

namespace App\Jobs;

use App\Models\Question;
use App\Models\AiResponse;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 300, 600]; // 1 min, 5 min, 10 min

    /**
     * The question ID to generate a response for.
     *
     * @var mixed
     */
    protected $questionId;

    /**
     * The AI service instance.
     *
     * @var \App\Services\AiService
     */
    protected $aiService;

    /**
     * Create a new job instance.
     *
     * @param  int|array|object  $questionId  The question ID, array containing question data, or question object
     * @return void
     */
    public function __construct($questionId)
    {
        // Handle different question ID formats
        if (is_array($questionId)) {
            // If it's an array with an 'id' key
            if (isset($questionId['id'])) {
                $this->questionId = $questionId['id'];
            } 
            // If it's a numeric array with ID at index 0
            elseif (isset($questionId[0]) && is_numeric($questionId[0])) {
                $this->questionId = $questionId[0];
            } else {
                $this->questionId = null;
            }
        } 
        // Handle case where question object is passed
        elseif (is_object($questionId) && isset($questionId->id)) {
            $this->questionId = $questionId->id;
        } 
        // Handle direct ID
        else {
            $this->questionId = $questionId;
        }
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\AiService  $aiService
     * @return bool|\App\Models\AiResponse
     */
    public function handle(AiService $aiService)
    {
        $this->aiService = $aiService;
        $maxAttempts = $this->tries;
        $attempt = 0;
        $lastError = null;
        
        // Ensure we have a valid question ID
        if (empty($this->questionId)) {
            Log::error('Cannot process AI response: Missing question ID');
            return false;
        }

        while ($attempt < $maxAttempts) {
            $attempt++;
            
            try {
                Log::info("Starting attempt {$attempt} of {$maxAttempts} for question #{$this->questionId}");
                
                return DB::transaction(function () use ($aiService, $attempt, $maxAttempts) {
                    // First, verify the question exists and is valid
                    if (!$this->questionId) {
                        $errorMsg = 'Invalid question ID provided';
                        Log::error($errorMsg, ['question_id' => $this->questionId]);
                        throw new \RuntimeException($errorMsg);
                    }

                    // Find the question with relationships and lock for update
                    $question = Question::with(['lesson', 'aiResponse'])
                        ->lockForUpdate()
                        ->find($this->questionId);
                        
                    if (!$question) {
                        $errorMsg = "Question #{$this->questionId} not found";
                        Log::error($errorMsg);
                        throw new \RuntimeException($errorMsg);
                    }

                    // Log question details for debugging
                    Log::debug('Processing question', [
                        'question_id' => $question->id,
                        'content' => $question->content,
                        'lesson_id' => $question->lesson_id,
                        'question_type' => get_class($question),
                        'question_json' => $question->toJson()
                    ]);

                    // Check if AI response already exists
                    if ($question->aiResponse) {
                        Log::info('AI response already exists for question #' . $question->id, [
                            'response_id' => $question->aiResponse->id,
                            'response_length' => strlen($question->aiResponse->answer)
                        ]);
                        return $question->aiResponse;
                    }

                    $lessonContent = $question->lesson ? $question->lesson->content : 'No lesson content available';
                    
                    // Generate the AI response
                    $startTime = microtime(true);
                    $aiResponseData = $aiService->generateResponse(
                        $question->content,
                        $lessonContent
                    );
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    // Extract answer and recommended lessons from the response
                    $answer = $aiResponseData['answer'] ?? 'No answer provided';
                    $recommendedLessons = $aiResponseData['recommended_lessons'] ?? [];
                    
                    Log::info("AI response generated in {$responseTime}ms", [
                        'question_id' => $question->id,
                        'response_length' => strlen($answer),
                        'recommended_lessons_count' => count($recommendedLessons)
                    ]);
                    
                    // Create the AI response
                    $aiResponse = new AiResponse([
                        'question_id' => $question->id,
                        'answer' => $answer,
                        'recommended_lessons' => $recommendedLessons
                    ]);
                    
                    $aiResponse->save();
                    
                    // Find and associate recommended lessons if any
                    if (!empty($recommendedLessons) && method_exists($aiResponse, 'recommendedLessons')) {
                        try {
                            // Find lessons that match the recommended topics
                            $matchingLessons = \App\Models\Lesson::whereIn('title', $recommendedLessons)
                                ->where('id', '!=', $question->lesson_id) // Don't recommend the current lesson
                                ->pluck('id')
                                ->toArray();
                            
                            if (!empty($matchingLessons)) {
                                $aiResponse->recommendedLessons()->sync($matchingLessons);
                                Log::info("Synced " . count($matchingLessons) . " recommended lessons for question #{$question->id}", [
                                    'lesson_ids' => $matchingLessons
                                ]);
                            } else {
                                Log::info("No matching lessons found for recommended topics", [
                                    'recommended_lessons' => $recommendedLessons
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to sync recommended lessons for question #{$question->id}", [
                                'error' => $e->getMessage(),
                                'recommended_lessons' => $recommendedLessons,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                    
                    Log::info('Successfully generated AI response for question #' . $question->id);
                    return $aiResponse;
                });
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Log::error("Error generating AI response (Attempt {$attempt}/{$maxAttempts})", [
                    'question_id' => $this->questionId,
                    'error' => $errorMessage,
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Create a fallback response
                try {
                    // Make sure we have a valid question ID
                    $questionId = $this->questionId;
                    
                    // Handle different question ID formats
                    if (is_array($questionId)) {
                        $questionId = $questionId['id'] ?? null;
                    } elseif (is_object($questionId) && isset($questionId->id)) {
                        $questionId = $questionId->id;
                    }
                    
                    if (!$questionId) {
                        Log::error('Cannot create fallback response: Invalid question ID', [
                            'question_id' => $this->questionId,
                            'question_id_type' => gettype($this->questionId)
                        ]);
                        throw $e; // Re-throw the original exception
                    }
                    
                    // Ensure question_id is an integer
                    $questionId = (int)$questionId;
                    
                    // Check if the question exists
                    if (!\App\Models\Question::where('id', $questionId)->exists()) {
                        Log::error('Cannot create fallback response: Question does not exist', [
                            'question_id' => $questionId
                        ]);
                        throw $e; // Re-throw the original exception
                    }
                    
                    // Create or update the AI response
                    AiResponse::updateOrCreate(
                        ['question_id' => $questionId],
                        [
                            'answer' => 'We encountered an issue generating a response. Please try again later or contact support if the issue persists.',
                            'recommended_lessons' => []
                        ]
                    );
                    
                    Log::info("Created fallback response for question #{$questionId}");
                    
                } catch (\Exception $updateError) {
                    Log::error("Failed to create fallback response for question #{$this->questionId}", [
                        'error' => $updateError->getMessage(),
                        'question_id_type' => gettype($this->questionId),
                        'question_id_value' => is_scalar($this->questionId) ? $this->questionId : json_encode($this->questionId),
                        'trace' => $updateError->getTraceAsString()
                    ]);
                    throw $e; // Re-throw the original exception
                }
                
                // If we've exhausted all attempts, log the final failure
                if ($attempt >= $maxAttempts) {
                    $lastError = $e;
                    $errorMessage = $e->getMessage();
                    Log::error("Failed to generate AI response after {$maxAttempts} attempts", [
                        'question_id' => $this->questionId,
                        'error' => $errorMessage,
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Ensure we have some response in the database
                    try {
                        // Make sure we have a valid question ID
                        $questionId = $this->questionId;
                        
                        // Handle different question ID formats
                        if (is_array($questionId)) {
                            $questionId = $questionId['id'] ?? null;
                        } elseif (is_object($questionId) && isset($questionId->id)) {
                            $questionId = $questionId->id;
                        }
                        
                        if (!$questionId) {
                            Log::error('Cannot create fallback response: Invalid question ID', [
                                'question_id' => $this->questionId,
                                'question_id_type' => gettype($this->questionId)
                            ]);
                            return false;
                        }
                        
                        // Ensure question_id is an integer
                        $questionId = (int)$questionId;
                        
                        // Check if the question exists
                        if (!\App\Models\Question::where('id', $questionId)->exists()) {
                            Log::error('Cannot create fallback response: Question does not exist', [
                                'question_id' => $questionId
                            ]);
                            return false;
                        }
                        
                        // Create or update the AI response
                        AiResponse::updateOrCreate(
                            ['question_id' => $questionId],
                            [
                                'answer' => 'We encountered an issue generating a response. Please try again later or contact support if the issue persists.',
                                'recommended_lessons' => []
                            ]
                        );
                        
                        Log::info("Created fallback response for question #{$questionId}");
                        
                    } catch (\Exception $updateError) {
                        Log::error("Failed to create final fallback response for question #{$this->questionId}", [
                            'error' => $updateError->getMessage(),
                            'question_id_type' => gettype($this->questionId),
                            'question_id_value' => is_scalar($this->questionId) ? $this->questionId : json_encode($this->questionId),
                            'trace' => $updateError->getTraceAsString()
                        ]);
                    }
                    
                    return false;
                }
                
                // Exponential backoff before retry
                $backoff = min(pow(2, $attempt - 1) * 1000, 10000); // Max 10 seconds
                Log::info("Waiting {$backoff}ms before retry (Attempt {$attempt}/{$maxAttempts})");
                usleep($backoff * 1000); // Convert to microseconds
                $attempt++;
            }
        }
        
        // If we get here, all attempts failed
        Log::error("All {$maxAttempts} attempts failed for question #{$this->questionId}", [
            'last_error' => $lastError ? $lastError->getMessage() : 'No error recorded'
        ]);
        
        return false;
    }
}
