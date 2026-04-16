<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineQuiz extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'subject_id', 'created_by', 'title', 'description', 'type',
        'duration_minutes', 'total_marks', 'pass_marks', 'shuffle_questions',
        'shuffle_options', 'show_result_immediately', 'status', 'start_at', 'end_at',
        'target_classes', 'target_sections',
    ];

    protected $casts = [
        'shuffle_questions'        => 'boolean',
        'shuffle_options'          => 'boolean',
        'show_result_immediately'  => 'boolean',
        'start_at'                 => 'datetime',
        'end_at'                   => 'datetime',
        'target_classes'           => 'array',
        'target_sections'          => 'array',
        'total_marks'              => 'float',
        'pass_marks'               => 'float',
    ];

    public function school()    { return $this->belongsTo(School::class); }
    public function subject()   { return $this->belongsTo(Subject::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function questions()  { return $this->hasMany(OnlineQuizQuestion::class, 'quiz_id')->orderBy('order'); }
    public function attempts()   { return $this->hasMany(OnlineQuizAttempt::class, 'quiz_id'); }

    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'published') return false;
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) return false;
        if ($this->end_at   && $now->gt($this->end_at))   return false;
        return true;
    }
}
