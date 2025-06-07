<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AiResponse extends Model
{
    protected $fillable = [
        'answer',
        'question_id',
        'recommended_lessons',
    ];

    protected $casts = [
        'recommended_lessons' => 'array',
    ];
    
    // For backward compatibility
    protected $appends = ['response', 'recommended_lesson_ids'];
    
    public function getResponseAttribute()
    {
        return $this->attributes['answer'] ?? null;
    }
    
    public function getRecommendedLessonsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            // If it's already in the correct format, return as is
            if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['id'])) {
                return $decoded;
            }
            // If it's an array of IDs, convert to the expected format
            if (is_array($decoded) && !empty($decoded) && is_numeric($decoded[0])) {
                return collect($decoded)->map(function($id) {
                    return ['id' => $id, 'title' => 'Lesson #' . $id];
                })->toArray();
            }
            return [];
        }
        return $value ?? [];
    }
    
    public function setRecommendedLessonsAttribute($value)
    {
        if (is_array($value)) {
            // If it's an array of objects with id/title, store as is
            if (!empty($value) && is_array($value[0] ?? null)) {
                $this->attributes['recommended_lessons'] = json_encode($value);
            } 
            // If it's an array of IDs, convert to objects
            elseif (!empty($value) && is_numeric($value[0] ?? null)) {
                $this->attributes['recommended_lessons'] = json_encode(
                    array_map(fn($id) => ['id' => $id, 'title' => 'Lesson #' . $id], $value)
                );
            } else {
                $this->attributes['recommended_lessons'] = json_encode([]);
            }
        } else {
            $this->attributes['recommended_lessons'] = $value;
        }
    }
    
    public function getRecommendedLessonIdsAttribute()
    {
        return collect($this->recommended_lessons)->pluck('id')->all();
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
    
    /**
     * Get the recommended lessons for this AI response.
     */
    public function recommendedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'ai_response_lesson')
            ->withPivot('relevance_score')
            ->withTimestamps();
    }
}
