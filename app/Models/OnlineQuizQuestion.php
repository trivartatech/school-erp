<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineQuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question_text', 'type', 'marks', 'options',
        'correct_answer', 'explanation', 'order',
    ];

    protected $casts = [
        'options' => 'array',
        'marks'   => 'float',
    ];

    public function quiz()      { return $this->belongsTo(OnlineQuiz::class, 'quiz_id'); }
    public function responses() { return $this->hasMany(OnlineQuizResponse::class, 'question_id'); }
}
