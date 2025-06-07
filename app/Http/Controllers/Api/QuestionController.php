<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    /**
     * Get all questions for a lesson with their AI responses.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($lesson)
    {
        try {
            // Debug: Log the incoming request
            Log::info('API Request to fetch questions', [
                'input' => $lesson,
                'type' => is_object($lesson) ? get_class($lesson) : gettype($lesson)
            ]);

            // Handle both ID and model binding
            if (!($lesson instanceof Lesson)) {
                $lesson = Lesson::with('questions')->find($lesson);
                
                if (!$lesson) {
                    Log::error('Lesson not found', ['id' => $lesson]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Lesson not found',
                        'lesson_id' => $lesson
                    ], 404);
                }
            }

            // Debug: Log the lesson ID and check if we have a valid lesson
            Log::info('Fetching questions for lesson', [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'questions_count' => $lesson->questions()->count()
            ]);

            // Get questions with relationships
            $questions = $lesson->questions()
                ->with([
                    'user:id,name',
                    'aiResponse.recommendedLessons' => function($query) {
                        $query->select('lessons.id', 'lessons.title', 'lessons.slug');
                    }
                ])
                ->latest()
                ->get()
                ->map(function ($question) {
                    $aiResponse = null;
                    
                    if ($question->aiResponse) {
                        // Get recommended lessons from the pivot table
                        $recommendedLessons = $question->aiResponse->recommendedLessons->map(function($lesson) {
                            return [
                                'id' => $lesson->id,
                                'title' => $lesson->title,
                                'slug' => $lesson->slug ?? null
                            ];
                        });
                        
                        // Fallback to the old JSON format if no lessons in the pivot table
                        if ($recommendedLessons->isEmpty() && !empty($question->aiResponse->recommended_lessons)) {
                            $recommendedLessons = collect($question->aiResponse->recommended_lessons)->map(function($lesson) {
                                return [
                                    'id' => $lesson['id'] ?? null,
                                    'title' => $lesson['title'] ?? 'Lesson #' . ($lesson['id'] ?? 'N/A'),
                                    'slug' => $lesson['slug'] ?? null
                                ];
                            });
                        }
                        
                        $aiResponse = [
                            'answer' => $question->aiResponse->answer,
                            'recommended_lessons' => $recommendedLessons->toArray(),
                            'updated_at' => $question->aiResponse->updated_at->toIso8601String()
                        ];
                    }
                    
                    return [
                        'id' => $question->id,
                        'content' => $question->content,
                        'created_at' => $question->created_at->toIso8601String(),
                        'user' => [
                            'id' => $question->user->id,
                            'name' => $question->user->name
                        ],
                        'ai_response' => $aiResponse
                    ];
                });

            return response()->json($questions)
                ->header('Access-Control-Allow-Origin', config('app.url'))
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
                
        } catch (\Exception $e) {
            Log::error('Error fetching questions: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified question with its AI response.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($question)
    {
        // Handle both ID and model binding
        if (!($question instanceof Question)) {
            $question = Question::with([
                'aiResponse.recommendedLessons',
                'user:id,name',
                'aiResponse.recommendedLessons' => function($query) {
                    $query->select('lessons.id', 'lessons.title', 'lessons.slug');
                }
            ])->findOrFail($question);
        } else {
            // Eager load the relationships if we got a model
            $question->load([
                'aiResponse.recommendedLessons',
                'user:id,name',
                'aiResponse.recommendedLessons' => function($query) {
                    $query->select('lessons.id', 'lessons.title', 'lessons.slug');
                }
            ]);
        }
        
        // Format the response to match frontend expectations
        $formattedQuestion = [
            'id' => $question->id,
            'content' => $question->content,
            'created_at' => $question->created_at->toIso8601String(),
            'user' => [
                'id' => $question->user->id,
                'name' => $question->user->name
            ],
            'ai_response' => null
        ];
        
        if ($question->aiResponse) {
            // Format recommended lessons for the response
            $recommendedLessons = $question->aiResponse->recommendedLessons->map(function($lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug
                ];
            });
            
            $formattedQuestion['ai_response'] = [
                'answer' => $question->aiResponse->answer,
                'recommended_lessons' => $recommendedLessons->toArray(),
                'updated_at' => $question->aiResponse->updated_at->toIso8601String()
            ];
        }
        
        return response()->json($formattedQuestion);
    }
}
