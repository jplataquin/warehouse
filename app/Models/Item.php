<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = ['type', 'item_type_id', 'name', 'specification', 'unit', 'current_warehouse_id', 'status', 'is_asset_utilized', 'is_approved', 'photo'];

    protected $attributes = [
        'status' => 'Operational',
    ];

    protected $casts = [
        'is_asset_utilized' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function getTypeAttribute()
    {
        return $this->itemType ? $this->itemType->base_behavior : null;
    }

    public function setTypeAttribute($value)
    {
        if ($value) {
            if (is_numeric($value)) {
                $this->attributes['item_type_id'] = (int) $value;
            } else {
                $itemType = ItemType::where('base_behavior', $value)
                    ->orWhere('name', $value)
                    ->first();
                if ($itemType) {
                    $this->attributes['item_type_id'] = $itemType->id;
                }
            }
        }
    }

    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function assetUtilizations()
    {
        return $this->hasMany(AssetUtilization::class);
    }

    public function currentWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
    }

    public function stockLevelRegistries()
    {
        return $this->hasMany(StockLevelRegistry::class);
    }

    public function latestUtilizeLedger()
    {
        return $this->hasOne(Ledger::class)
            ->where('type', 'OUT')
            ->where('action', 'UTILIZE')
            ->latest('id');
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
