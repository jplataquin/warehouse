<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\LedgerDiscrepancy;

class DiscrepancyDiagnosticService
{
    /**
     * Run a diagnostic check to find if an entry caused a negative balance for the item in the warehouse.
     * If a discrepancy is found, automatically log it to ledger_discrepancies if not already logged as DISCREPANCY.
     *
     * @param int $warehouseId
     * @param int $itemId
     * @return array|null
     */
    public function checkAndLog(int $warehouseId, int $itemId): ?array
    {
        $ledgers = Ledger::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'entry_date', 'type', 'quantity', 'action']);

        $running = 0.0;
        $firstNegative = null;

        foreach ($ledgers as $ledger) {
            if ($ledger->type === 'IN') {
                $running += (float) $ledger->quantity;
            } else {
                $running -= (float) $ledger->quantity;
            }

            if (round($running, 4) < 0) {
                $firstNegative = [
                    'entry_id' => $ledger->id,
                    'balance' => $running,
                    'entry_date' => $ledger->entry_date ? $ledger->entry_date->format('M d, Y') : null,
                    'action' => $ledger->action,
                    'quantity' => (float) $ledger->quantity,
                ];
                break;
            }
        }

        if ($firstNegative) {
            $existing = LedgerDiscrepancy::where('ledger_id', $firstNegative['entry_id'])
                ->where('status', 'DISCREPANCY')
                ->exists();

            if (! $existing) {
                LedgerDiscrepancy::create([
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'ledger_id' => $firstNegative['entry_id'],
                    'status' => 'DISCREPANCY',
                ]);
            }
        }

        return $firstNegative;
    }
}
