<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = ['project_id', 'type', 'name', 'status'];

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Active', 'ACTIVE']);
    }

    protected static function booted()
    {
        static::created(function ($warehouse) {
            $warehouse->allocations()->create([
                'name' => 'No Allocation',
            ]);
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function loggers()
    {
        return $this->belongsToMany(User::class, 'warehouse_loggers');
    }

    public function allocations()
    {
        return $this->hasMany(Allocation::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
