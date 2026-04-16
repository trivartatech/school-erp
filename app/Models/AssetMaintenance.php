<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    protected $fillable = [
        'asset_id', 'school_id', 'reported_on', 'issue_description', 'type',
        'status', 'cost', 'resolved_on', 'vendor', 'resolution_notes', 'reported_by',
    ];

    protected $casts = [
        'reported_on' => 'date',
        'resolved_on' => 'date',
        'cost'        => 'decimal:2',
    ];

    public function asset()      { return $this->belongsTo(Asset::class); }
    public function reportedBy() { return $this->belongsTo(User::class, 'reported_by'); }
}
