<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Lesson;
use App\Jobs\GenerateAiResponse;
use App\Models\AiResponse;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class QuestionController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Show the form for asking a question about a lesson.
     */
    public function create(Lesson $lesson)
    {
        $this->authorize('view', $lesson);
        
        return Inertia::render('Questions/Create', [
            'lesson' => $lesson->only(['id', 'title']),
        ]);
    }

    /**
     * Store a newly created question in storage.
     *
     * @param  \App\Models\Lesson  $lesson
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Lesson $lesson, Request $request)
    {
        $this->authorize('view', $lesson);
        
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);
        
        try {
            // Create the question
            $question = $lesson->questions()->create([
                'user_id' => auth()->id(),
                'content' => $validated['content'],
            ]);
            
            // Dispatch job to generate AI response
            GenerateAiResponse::dispatch($question);
            
            // For web requests, redirect back to the lesson page
            return redirect()->route('lessons.show', [
                'lesson' => $lesson->id,
                'question' => $question->id
            ])->with('success', 'Your question has been submitted. The AI is processing it now.');
                
        } catch (\Exception $e) {
            Log::error('Error creating question: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to submit your question. Please try again.');
        }
    }

    /**
     * Generate AI response for a question.
     *
     * @param  \App\Models\Question  $question
     * @return void
     */
    protected function generateAiResponse(Question $question): void
    {
        // If there's already an AI response, don't generate a new one
        if ($question->aiResponse) {
            return;
        }

        // Dispatch the job to generate the AI response
        GenerateAiResponse::dispatch($question);
    }

    /**
     * Display the specified question with AI response.
     *
     * @param  \App\Models\Question  $question
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response|\Illuminate\Http\JsonResponse
     */
    public function show(Question $question, Request $request)
    {
        // Eager load the AI response and user relationship
        $question->load(['aiResponse', 'user']);

        // If there's no AI response yet, try to generate one
        if (!$question->aiResponse) {
            try {
                // Dispatch the job to generate AI response
                dispatch(new GenerateAiResponse($question));
                
                // Return immediately with the current state
                return $this->formatQuestionResponse($question, $request);
            } catch (\Exception $e) {
                // Log the error but don't fail the request
                \Log::error('Error dispatching AI response job: ' . $e->getMessage());
            }
        }

        return $this->formatQuestionResponse($question, $request);
    }

    /**
     * Format the question response based on the request type
     *
     * @param \App\Models\Question $question
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response
     */
    /**
     * Format recommended lessons to ensure consistent structure
     *
     * @param mixed $recommendedLessons
     * @return array
     */
    protected function formatRecommendedLessons($recommendedLessons)
    {
        if (empty($recommendedLessons)) {
            return [];
        }

        // If it's a JSON string, decode it
        if (is_string($recommendedLessons)) {
            $recommendedLessons = json_decode($recommendedLessons, true);
        }

        // If it's not an array, return empty array
        if (!is_array($recommendedLessons)) {
            return [];
        }

        // Ensure each item has a consistent structure
        return array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }
            
            if (is_array($item)) {
                return [
                    'id' => $item['id'] ?? null,
                    'title' => $item['title'] ?? 'Untitled Lesson',
                    'description' => $item['description'] ?? null,
                ];
            }
            
            if (is_object($item)) {
                return [
                    'id' => $item->id ?? null,
                    'title' => $item->title ?? 'Untitled Lesson',
                    'description' => $item->description ?? null,
                ];
            }
            
            return 'Untitled Lesson';
        }, $recommendedLessons);
    }

    /**
     * Format the question response based on the request type
     *
     * @param \App\Models\Question $question
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response
     */
    protected function formatQuestionResponse(Question $question, Request $request)
    {
        $responseData = [
            'id' => $question->id,
            'content' => $question->content,
            'created_at' => $question->created_at->toDateTimeString(),
            'updated_at' => $question->updated_at->toDateTimeString(),
            'user' => $question->user ? [
                'id' => $question->user->id,
                'name' => $question->user->name,
                'email' => $question->user->email,
            ] : null,
            'ai_response' => null,
        ];

        if ($question->aiResponse) {
            $responseData['ai_response'] = [
                'id' => $question->aiResponse->id,
                'answer' => $question->aiResponse->answer,
                'recommended_lessons' => $this->formatRecommendedLessons($question->aiResponse->recommended_lessons),
                'created_at' => $question->aiResponse->created_at->toDateTimeString(),
                'updated_at' => $question->aiResponse->updated_at->toDateTimeString(),
            ];
        }

        // If this is an API request or JSON is expected
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        }

        // Return Inertia response for web requests
        // Redirect to the lesson show page with the question ID as a query parameter
        return redirect()->route('lessons.show', [
            'lesson' => $question->lesson_id,
            'question' => $question->id
        ]);
    }
}
