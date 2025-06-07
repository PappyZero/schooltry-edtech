<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AiService
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        $this->model = config('services.openrouter.model', 'google/gemma-7b-it:free');
        $this->apiUrl = config('services.openrouter.api_url', 'https://openrouter.ai/api/v1/chat/completions');
        
        if (empty($this->apiKey)) {
            Log::warning('OpenRouter API key is not configured. Some AI features may not work.');
        }

        Log::info('Initialized AI Service with OpenRouter', [
            'model' => $this->model,
            'api_url' => $this->apiUrl,
            'api_key' => $this->apiKey ? 'set' : 'not set'
        ]);
    }

    /**
     * Generate an AI response to a student's question
     *
     * @param string $question The student's question
     * @param string $lessonContent The content of the current lesson
     * @return string The AI-generated response
     * @throws \RuntimeException If the API request fails
     */
    /**
     * Generate an AI response to a student's question with recommended lessons
     *
     * @param string $question The student's question
     * @return array Returns an array with 'answer' and 'recommended_lessons'
     * @throws \RuntimeException If the API request fails
     */
    public function generateResponse(string $question, string $lessonContent): array
    {
        $requestId = uniqid('ai_');
        $startTime = microtime(true);
        
        try {
            Log::info("[$requestId] Preparing OpenRouter API request", [
                'model' => $this->model,
                'question_length' => strlen($question),
                'content_length' => strlen($lessonContent)
            ]);

            if (empty($this->apiKey)) {
                throw new \RuntimeException('OpenRouter API key is not configured');
            }

            // Prepare the system message with instructions for the AI
            $systemMessage = "You are a helpful teaching assistant. When responding to student questions, please follow these guidelines:\n" .
                          "1. Provide a clear, detailed answer to the student's question.\n" .
                          "2. At the end of your response, include a JSON array of 1-3 recommended lesson topics that would help the student understand the topic better.\n" .
                          "3. Format the JSON array like this: [\"topic 1\", \"topic 2\"]\n\n" .
                          "Example response:\n" .
                          "Here's a detailed explanation of the topic...\n\n" .
                          "```json\n" .
                          "[\"Introduction to Programming\", \"Basic Data Structures\", \"Control Flow in Programming\"]\n" .
                          "```";

            // Prepare the prompt for the model
            $prompt = "Lesson content:\n\n$lessonContent\n\n" .
                     "Student's question: $question";

            $requestData = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7,
                'response_format' => ['type' => 'text'],
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            Log::debug("[$requestId] Sending request to OpenRouter API", [
                'url' => $this->apiUrl,
                'headers' => array_merge($headers, ['Authorization' => 'Bearer ' . substr($this->apiKey, 0, 6) . '...']),
                'payload' => array_merge($requestData, ['messages' => '[REDACTED]'])
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->withOptions([
                    'verify' => true,
                    'http_errors' => false,
                ])
                ->post($this->apiUrl, $requestData);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $responseBody = $response->body();
            $responseData = json_decode($responseBody, true) ?? [];
            $status = $response->status();
            
            Log::debug("[$requestId] Received response from OpenRouter API", [
                'status' => $status,
                'duration_ms' => $duration,
                'response_keys' => array_keys($responseData)
            ]);

            if (!$response->successful()) {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
                $errorType = $responseData['error']['type'] ?? 'unknown';
                
                Log::error("[$requestId] API error response", [
                    'status' => $status,
                    'error_type' => $errorType,
                    'error_message' => $errorMessage,
                    'response' => $responseData
                ]);
                
                throw new \RuntimeException("API request failed: {$errorMessage} (Status: {$status})");
            }

            $fullResponse = $responseData['choices'][0]['message']['content'] ?? '';
            
            // Initialize default values
            $answer = $fullResponse;
            $recommendedLessons = [];
            
            // Try to extract JSON array of recommended lessons from the response
            if (preg_match('/```json\s*(\[.*?\])\s*```/s', $fullResponse, $matches)) {
                // Found JSON array in code block
                $jsonStr = $matches[1];
                $recommendedLessons = json_decode($jsonStr, true) ?: [];
                // Remove the JSON part from the answer
                $answer = trim(str_replace($matches[0], '', $fullResponse));
            } elseif (preg_match('/\[.*\]/', $fullResponse, $matches)) {
                // Try to find any JSON-like array in the response
                $jsonStr = $matches[0];
                $decoded = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $recommendedLessons = $decoded;
                    // Remove the JSON part from the answer
                    $answer = trim(str_replace($jsonStr, '', $fullResponse));
                }
            }
            
            // Clean up the answer
            $answer = preg_replace('/\s*Recommended lessons:.*$/is', '', $answer);
            $answer = trim($answer);
            
            // Ensure recommended_lessons is an array of strings
            $recommendedLessons = is_array($recommendedLessons) 
                ? array_values(array_filter(array_map('trim', $recommendedLessons), 'is_string'))
                : [];
            
            // Log the extracted information
            Log::info("[$requestId] Successfully generated AI response", [
                'answer_length' => strlen($answer),
                'recommended_lessons_count' => count($recommendedLessons),
                'recommended_lessons' => $recommendedLessons,
                'first_100_chars' => substr($answer, 0, 100) . (strlen($answer) > 100 ? '...' : '')
            ]);
            
            return [
                'answer' => $answer,
                'recommended_lessons' => $recommendedLessons
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::error("[$requestId] Request failed after {$duration}ms", [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $this->model ?? 'not set',
                'api_url' => $this->apiUrl ?? 'not set',
                'api_key_set' => !empty($this->apiKey)
            ]);
            
            return [
                'answer' => "I'm sorry, I encountered an error while processing your request. The issue has been logged.",
                'recommended_lessons' => []
            ];
        }
    }

    /**
     * Find lessons similar to the question
     *
     * @param string $question The question to find similar lessons for
     * @param int $currentLessonId The ID of the current lesson to exclude from results
     * @return \Illuminate\Support\Collection Collection of similar lessons with id and title
     */
    public function findSimilarLessons(string $question, int $currentLessonId): \Illuminate\Support\Collection
    {
        try {
            $query = \App\Models\Lesson::where('id', '!=', $currentLessonId)
                ->where(function($q) use ($question) {
                    // Basic full-text search
                    $q->where('title', 'like', "%$question%")
                      ->orWhere('content', 'like', "%$question%");
                });

            // If no results from direct search, try a broader search
            if ($query->count() < 3) {
                $keywords = $this->extractKeywords($question);
                if (!empty($keywords)) {
                    $query->orWhere(function($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $q->orWhere('title', 'like', "%$keyword%")
                              ->orWhere('content', 'like', "%$keyword%");
                        }
                    });
                }
            }

            return $query->limit(3)
                ->get()
                ->map(fn($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                ]);

        } catch (\Exception $e) {
            Log::error('Error finding similar lessons: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Extract keywords from a question for improved search
     */
    protected function extractKeywords(string $text): array
    {
        // Remove common words and punctuation
        $stopWords = ['the', 'and', 'or', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'how', 'what', 'when', 'where', 'why'];
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', strtolower($text));

        return array_filter(array_unique($words), function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
    }
}
