<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Admin routes
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::resource('lessons', AdminController::class)->names([
            'index' => 'admin.lessons.index',
            'create' => 'admin.lessons.create',
            'store' => 'admin.lessons.store',
            'edit' => 'admin.lessons.edit',
            'update' => 'admin.lessons.update',
            'destroy' => 'admin.lessons.destroy'
        ]);
    });

    // Dashboard
    Route::get('/dashboard', function () {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.lessons.index');
        }
        return redirect()->route('lessons.index');
    })->name('dashboard');

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Test route to check lesson loading
    Route::get('/test/lesson/{lesson}', function (App\Models\Lesson $lesson) {
        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'questions_count' => $lesson->questions()->count(),
            'questions' => $lesson->questions()->with('aiResponse')->get()
        ]);
    })->middleware('auth');

    // Student routes
    Route::prefix('lessons')->name('lessons.')->group(function () {
        Route::get('/', [StudentLessonController::class, 'index'])->name('index');
        Route::get('/{lesson}', [StudentLessonController::class, 'show'])->name('show');
        
        // Questions
        Route::post('/{lesson}/questions', [QuestionController::class, 'store'])
            ->name('questions.store');
    });
    
    // Question routes
    Route::get('/questions/{question}', [QuestionController::class, 'show'])
        ->name('questions.show');
        
    // Test route to generate AI response (temporary)
    Route::get('/test/generate-ai-response/{questionId}', function ($questionId) {
        try {
            // Bypass auth for testing
            if (!auth()->check()) {
                auth()->loginUsingId(1); // Log in as user ID 1 for testing
            }
            
            $question = \App\Models\Question::with('lesson')->findOrFail($questionId);
            
            if (!$question->lesson) {
                throw new \Exception('Question is not associated with a lesson');
            }
            
            $aiService = new \App\Services\AiService();
            
            // Log the start of the process
            \Illuminate\Support\Facades\Log::info('Generating AI response', [
                'question_id' => $question->id,
                'question' => $question->content,
                'lesson_id' => $question->lesson->id
            ]);
            
            $aiAnswer = $aiService->generateResponse(
                $question->content,
                $question->lesson->content
            );
            
            // For now, just return the AI response without saving
            return response()->json([
                'success' => true,
                'message' => 'AI response generated successfully',
                'question_id' => $question->id,
                'response' => $aiAnswer
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating AI response', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating AI response: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    })->middleware('auth');
});

require __DIR__.'/auth.php';
