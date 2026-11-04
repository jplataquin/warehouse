<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerDiscrepancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'ledger_id',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class)->withTrashed();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
