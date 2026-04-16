<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineQuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id', 'student_id', 'school_id', 'started_at', 'submitted_at',
        'score', 'percentage', 'passed', 'status', 'tab_switches',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'score'        => 'float',
        'percentage'   => 'float',
        'passed'       => 'boolean',
    ];

    public function quiz()      { return $this->belongsTo(OnlineQuiz::class, 'quiz_id'); }
    public function student()   { return $this->belongsTo(Student::class); }
    public function responses() { return $this->hasMany(OnlineQuizResponse::class, 'attempt_id'); }
}
