<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allocation extends Model
{
    use SoftDeletes;

    protected $fillable = ['warehouse_id', 'name', 'mapped_to_component_id'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
