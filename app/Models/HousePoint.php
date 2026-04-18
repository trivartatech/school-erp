<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'house_id',
        'academic_year_id',
        'category',
        'points',
        'description',
        'reference_type',
        'reference_id',
        'awarded_by',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
