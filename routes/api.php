<?php

use App\Http\Controllers\Api\QuestionController;
use App\Models\AiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public test route to check AI response
Route::get('/test/ai-response/{questionId}', function ($questionId) {
    $response = \App\Models\AiResponse::where('question_id', $questionId)->first();
    
    if (!$response) {
        return response()->json([
            'success' => false,
            'message' => 'No AI response found for this question',
            'question_id' => $questionId
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'question_id' => $questionId,
        'response' => $response->answer,
        'created_at' => $response->created_at
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Get all questions for a lesson
    Route::get('/lessons/{lesson}/questions', [QuestionController::class, 'index'])->name('api.lessons.questions');
    
    // Get a single question with its AI response
    Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('api.questions.show');
});
