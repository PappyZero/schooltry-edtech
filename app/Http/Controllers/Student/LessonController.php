<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Display a listing of the lessons for students.
     */
    public function index()
    {
        $lessons = Lesson::withCount('questions')
            ->latest()
            ->paginate(10);

        return Inertia::render('Student/Lessons/Index', [
            'lessons' => $lessons,
        ]);
    }

    /**
     * Display the specified lesson for students.
     */
    public function show(Lesson $lesson, Request $request)
    {
        // Debug: Log the lesson ID and title
        \Log::info('Showing lesson', [
            'lesson_id' => $lesson->id,
            'title' => $lesson->title,
            'question_id' => $request->query('question')
        ]);

        // Eager load questions with their AI responses and users
        $lesson->load(['questions' => function ($query) {
            $query->with(['aiResponse', 'user'])->latest();
        }]);
        
        // Debug: Log the raw questions and their relationships
        \Log::debug('Raw questions with relationships', [
            'questions' => $lesson->questions->map(function($q) {
                return [
                    'id' => $q->id,
                    'content' => $q->content,
                    'has_ai_response' => $q->relationLoaded('aiResponse') && $q->aiResponse,
                    'ai_response' => $q->relationLoaded('aiResponse') ? [
                        'id' => $q->aiResponse?->id,
                        'answer' => $q->aiResponse?->answer ? '*** answer exists ***' : 'no answer',
                        'recommended_lessons' => $q->aiResponse?->recommended_lessons ? 'exists' : 'none',
                    ] : 'not loaded',
                    'has_user' => $q->relationLoaded('user') && $q->user,
                ];
            })->toArray()
        ]);
        
        // Debug: Log the loaded questions
        \Log::info('Loaded questions', [
            'count' => $lesson->questions->count(),
            'questions' => $lesson->questions->map(fn($q) => [
                'id' => $q->id,
                'has_ai_response' => $q->aiResponse ? true : false,
                'ai_response_id' => $q->aiResponse?->id,
                'has_user' => $q->user ? true : false,
            ])->toArray(),
        ]);

        // Transform the data to ensure it's in the correct format for the frontend
        $formattedQuestions = $lesson->questions->map(function ($question) use ($lesson) {
            $aiResponse = $question->aiResponse;
            
            // Debug: Log each question's AI response
            $recommendedLessons = [];
            
            if ($aiResponse) {
                // Handle recommended_lessons whether it's JSON string or array
                $recommendedLessons = $aiResponse->recommended_lessons ?? [];
                if (is_string($recommendedLessons)) {
                    $recommendedLessons = json_decode($recommendedLessons, true) ?: [];
                }
                
                // Ensure recommended_lessons is always an array of strings
                if (!empty($recommendedLessons) && is_array($recommendedLessons)) {
                    if (isset($recommendedLessons[0]) && is_string($recommendedLessons[0])) {
                        // Already in the correct format
                    } elseif (isset($recommendedLessons[0]) && is_array($recommendedLessons[0])) {
                        // Convert from array of objects to array of strings
                        $recommendedLessons = array_map(function($item) {
                            return $item['title'] ?? $item['name'] ?? 'Unnamed Lesson';
                        }, $recommendedLessons);
                    }
                } else {
                    $recommendedLessons = [];
                }
                
                \Log::info('Processing question', [
                    'question_id' => $question->id,
                    'has_ai_response' => true,
                    'ai_response_id' => $aiResponse->id,
                    'has_answer' => !empty($aiResponse->answer),
                    'answer_length' => $aiResponse->answer ? strlen($aiResponse->answer) : 0,
                    'recommended_lessons_count' => count($recommendedLessons),
                    'recommended_lessons' => $recommendedLessons,
                ]);
            } else {
                \Log::info('Processing question', [
                    'question_id' => $question->id,
                    'has_ai_response' => false,
                ]);
            }

            return [
                'id' => $question->id,
                'content' => $question->content,
                'created_at' => $question->created_at->toIso8601String(),
                'updated_at' => $question->updated_at->toIso8601String(),
                'user' => $question->user ? [
                    'id' => $question->user->id,
                    'name' => $question->user->name,
                ] : null,
                'ai_response' => $aiResponse ? [
                    'id' => $aiResponse->id,
                    'answer' => $aiResponse->answer,
                    'recommended_lessons' => $recommendedLessons,
                    'created_at' => $aiResponse->created_at->toIso8601String(),
                    'updated_at' => $aiResponse->updated_at->toIso8601String(),
                ] : null,
            ];
        })->toArray();

        // Debug: Log the final data being sent to the view
        \Log::info('Sending lesson data to view', [
            'questions_count' => count($formattedQuestions),
        ]);

        return Inertia::render('Student/Lessons/Show', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'created_at' => $lesson->created_at->toIso8601String(),
                'updated_at' => $lesson->updated_at->toIso8601String(),
                'questions' => $formattedQuestions,
            ]
        ]);
    }
}
