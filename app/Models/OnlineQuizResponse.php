<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineQuizResponse extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'answer', 'is_correct', 'marks_awarded',
    ];

    protected $casts = [
        'is_correct'   => 'boolean',
        'marks_awarded'=> 'float',
    ];

    public function attempt()  { return $this->belongsTo(OnlineQuizAttempt::class, 'attempt_id'); }
    public function question() { return $this->belongsTo(OnlineQuizQuestion::class, 'question_id'); }
}
