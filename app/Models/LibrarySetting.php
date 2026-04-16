<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibrarySetting extends Model
{
    protected $fillable = [
        'school_id', 'max_issue_days', 'fine_per_day',
        'max_books_student', 'max_books_staff',
    ];

    protected $casts = [
        'fine_per_day' => 'float',
    ];

    public function school() { return $this->belongsTo(School::class); }
}
