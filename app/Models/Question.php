<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Question extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'lesson_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson that the question belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the AI response associated with the question.
     */
    public function aiResponse(): HasOne
    {
        return $this->hasOne(AiResponse::class, 'question_id');
    }

    /**
     * Scope a query to only include questions for a specific lesson.
     */
    public function scopeForLesson(Builder $query, int $lessonId): Builder
    {
        return $query->where('lesson_id', $lessonId);
    }

    /**
     * Scope a query to only include questions from a specific user.
     */
    public function scopeFromUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
