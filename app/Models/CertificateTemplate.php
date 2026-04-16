<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'name',
        'orientation', 'background', 'elements', 'custom_vars',
    ];

    protected $casts = [
        'background'   => 'array',
        'elements'     => 'array',
        'custom_vars'  => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
