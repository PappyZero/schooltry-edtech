<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display a listing of the lessons.
     */
    public function index()
    {
        $this->authorize('viewAny', Lesson::class);
        
        // If user is admin, show all lessons, otherwise show only their own lessons
        $query = Auth::user()->is_admin 
            ? Lesson::query()
            : Lesson::where('user_id', Auth::id());
            
        $lessons = $query->withCount('questions')
            ->latest()
            ->paginate(10);
            
        return Inertia::render('Admin/Lessons/Index', [
            'lessons' => $lessons,
        ]);
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        $this->authorize('create', Lesson::class);
        
        return Inertia::render('Admin/Lessons/Create');
    }

    /**
     * Store a newly created lesson in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Lesson::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
        
        $validated['user_id'] = Auth::id();
        
        try {
            /** @var User $user */
            $user = Auth::user();
            $lesson = $user->lessons()->create($validated);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lesson created successfully!',
                    'lesson' => $lesson
                ]);
            }
            
            return redirect()
                ->route('admin.lessons.index')
                ->with('success', 'Lesson created successfully!');
                
        } catch (\Exception $e) {
            Log::error('Error creating lesson: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create lesson. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create lesson. Please try again.');
        }
    }

    /**
     * Display the specified lesson.
     */
    public function show(Lesson $lesson)
    {
        $this->authorize('view', $lesson);
        
        return Inertia::render('Admin/Lessons/Show', [
            'lesson' => $lesson->load('questions'),
        ]);
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit(Lesson $lesson)
    {
        $this->authorize('update', $lesson);
        
        return Inertia::render('Admin/Lessons/Edit', [
            'lesson' => $lesson,
        ]);
    }

    /**
     * Update the specified lesson in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
        
        $lesson->update($validated);
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true, 
                'message' => 'Lesson updated successfully.',
                'lesson' => $lesson->fresh()
            ]);
        }
        
        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson updated successfully.');
    }

    /**
     * Remove the specified lesson from storage.
     */
    public function destroy(Lesson $lesson)
    {
        $this->authorize('delete', $lesson);
        
        $lesson->delete();
        
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Lesson deleted successfully.']);
        }
        
        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson deleted successfully.');
    }
}
