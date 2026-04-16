<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CertificateIssuance extends Model
{
    protected $fillable = [
        'school_id', 'template_id', 'student_id', 'certificate_no',
        'issued_date', 'custom_vals', 'verification_token', 'issued_by',
    ];

    protected $casts = [
        'custom_vals' => 'array',
        'issued_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->verification_token = $model->verification_token ?? Str::random(48);
        });
    }

    public function school()    { return $this->belongsTo(School::class); }
    public function template()  { return $this->belongsTo(CertificateTemplate::class, 'template_id'); }
    public function student()   { return $this->belongsTo(Student::class); }
    public function issuedBy()  { return $this->belongsTo(User::class, 'issued_by'); }
}
