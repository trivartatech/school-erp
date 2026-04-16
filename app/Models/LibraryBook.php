<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'title', 'author', 'isbn', 'publisher', 'publish_year',
        'category', 'subject', 'language', 'location', 'total_copies',
        'available_copies', 'price', 'cover_image', 'description', 'barcode',
    ];

    protected $casts = [
        'price'           => 'float',
        'total_copies'    => 'integer',
        'available_copies'=> 'integer',
    ];

    public function school()  { return $this->belongsTo(School::class); }
    public function issues()  { return $this->hasMany(LibraryIssue::class, 'book_id'); }

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0;
    }
}
