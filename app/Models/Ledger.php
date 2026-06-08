<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_date',
        'type', 'action', 'item_id', 'quantity', 'status',
        'po_number', 'offical_receipt', 'delivery_receipt',
        'warehouse_id', 'project_id', 'destination_warehouse_id', 'source_warehouse_id',
        'allocation_id', 'assigned_to', 'plate_no', 'remarks', 'linked_ledger_id',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'entry_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function linkedLedger()
    {
        return $this->belongsTo(Ledger::class, 'linked_ledger_id');
    }
}
