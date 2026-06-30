<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetUtilization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id',
        'utilized_by',
        'utilized_at',
        'returned_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'utilized_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
