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

    public function latestLedger()
    {
        return $this->hasOne(Ledger::class)
            ->latest('entry_date')
            ->latest('created_at')
            ->latest('id');
    }

    public function getHistoryWarehouseIdAttribute()
    {
        return $this->current_warehouse_id
            ?? $this->latestLedger?->warehouse_id
            ?? $this->latestUtilizeLedger?->warehouse_id;
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

    public function validateChronologicalBalance($warehouseId, $affectedDate = null)
    {
        $baseQuery = $this->ledgers()->where('warehouse_id', $warehouseId);

        $startingBalance = 0.0;

        if ($affectedDate) {
            $dateStr = $affectedDate instanceof \Carbon\CarbonInterface
                ? $affectedDate->toDateString()
                : (string) $affectedDate;

            if (strlen($dateStr) > 10) {
                $dateStr = substr($dateStr, 0, 10);
            }

            $priorQuery = (clone $baseQuery)->where(function ($q) use ($dateStr) {
                $q->where('entry_date', '<', $dateStr)
                  ->orWhereNull('entry_date');
            });
            $in = (clone $priorQuery)->where('type', 'IN')->sum('quantity');
            $out = (clone $priorQuery)->where('type', 'OUT')->sum('quantity');
            $startingBalance = (float) ($in - $out);

            $entriesQuery = (clone $baseQuery)->where('entry_date', '>=', $dateStr);
        } else {
            $entriesQuery = clone $baseQuery;
        }

        $ledgers = $entriesQuery
            ->orderBy('entry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'entry_date', 'type', 'quantity']);

        $running = $startingBalance;

        foreach ($ledgers as $ledger) {
            if ($ledger->type === 'IN') {
                $running += (float) $ledger->quantity;
            } else {
                $running -= (float) $ledger->quantity;
            }

            if (round($running, 4) < 0) {
                $formattedDate = $ledger->entry_date
                    ? ($ledger->entry_date instanceof \Carbon\CarbonInterface
                        ? $ledger->entry_date->format('Y-m-d')
                        : substr((string) $ledger->entry_date, 0, 10))
                    : 'N/A';
                throw new \Exception("Transaction would cause a negative historical balance of {$running} on {$formattedDate} (Ledger ID: {$ledger->id}) for '{$this->name}'.");
            }
        }
    }
}
