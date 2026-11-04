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
