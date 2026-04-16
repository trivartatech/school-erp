<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id', 'school_id', 'assignee_type', 'assignee_id',
        'location', 'assigned_on', 'returned_on', 'assigned_by', 'notes',
    ];

    protected $casts = [
        'assigned_on' => 'date',
        'returned_on' => 'date',
    ];

    public function asset()      { return $this->belongsTo(Asset::class); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
