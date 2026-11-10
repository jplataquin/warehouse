<?php

namespace App\Observers;

use App\Models\Ledger;
use App\Services\DiscrepancyDiagnosticService;

class LedgerObserver
{
    protected $diagnosticService;

    public function __construct(DiscrepancyDiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
    }

    /**
     * Handle the Ledger "saved" event.
     */
    public function saved(Ledger $ledger): void
    {
        if ($ledger->action === 'DISPOSE' && $ledger->status === 'APPROVED' && $ledger->item_id) {
            $item = $ledger->item ?: \App\Models\Item::find($ledger->item_id);
            if ($item && $item->type === 'ASSET' && $item->status !== 'Disposed') {
                $item->update([
                    'status' => 'Disposed',
                    'is_asset_utilized' => false,
                ]);
            }
        }

        if ($ledger->warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->warehouse_id, (int) $ledger->item_id);
        }

        // If transfer/movement affects destination warehouse or source warehouse
        if ($ledger->destination_warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->destination_warehouse_id, (int) $ledger->item_id);
        }

        if ($ledger->source_warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->source_warehouse_id, (int) $ledger->item_id);
        }
    }

    /**
     * Handle the Ledger "deleted" event.
     */
    public function deleted(Ledger $ledger): void
    {
        if ($ledger->warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->warehouse_id, (int) $ledger->item_id);
        }

        if ($ledger->destination_warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->destination_warehouse_id, (int) $ledger->item_id);
        }

        if ($ledger->source_warehouse_id && $ledger->item_id) {
            $this->diagnosticService->checkAndLog((int) $ledger->source_warehouse_id, (int) $ledger->item_id);
        }
    }
}
