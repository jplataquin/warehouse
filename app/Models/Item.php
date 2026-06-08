<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = ['type', 'name', 'specification', 'unit', 'current_warehouse_id'];

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function currentWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
    }

    public function getBalance($warehouseId = null)
    {
        $query = $this->ledgers();
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $in = (clone $query)->where('type', 'IN')->sum('quantity');
        $out = (clone $query)->where('type', 'OUT')->sum('quantity');

        return $in - $out;
    }
}
