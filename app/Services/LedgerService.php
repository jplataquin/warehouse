<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Exception;

class LedgerService
{
    /**
     * Create a new ledger entry with validation.
     */
    public function createEntry(array $data)
    {
        return DB::transaction(function () use ($data) {
            $item = Item::findOrFail($data['item_id']);
            
            // Auto-populate project_id if missing
            if (empty($data['project_id'])) {
                if (!empty($data['allocation_id'])) {
                    $allocation = \App\Models\Allocation::with('warehouse')->find($data['allocation_id']);
                    $data['project_id'] = ($allocation && $allocation->warehouse) ? $allocation->warehouse->project_id : null;
                }
                
                if (empty($data['project_id']) && !empty($data['warehouse_id'])) {
                    $warehouse = \App\Models\Warehouse::find($data['warehouse_id']);
                    $data['project_id'] = $warehouse ? $warehouse->project_id : null;
                }
            }

            $this->validateRules($item, $data);

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $entry = Ledger::create($data);

            // Update Item's current warehouse if ASSET
            if ($item->type === 'ASSET') {
                $item->update([
                    'current_warehouse_id' => $data['type'] === 'IN' ? $data['warehouse_id'] : null
                ]);
                $item->refresh(); // Refresh to ensure subsequent validations in the same transaction see the update
            }

            // Enforced Automatic Entry IN for Transfers
            if ($data['type'] === 'OUT' && $data['action'] === 'TRANSFER') {
                $inData = $data;
                $inData['type'] = 'IN';
                $inData['warehouse_id'] = $data['destination_warehouse_id'];
                // Swap source and destination for the IN entry to represent the arrival
                $inData['source_warehouse_id'] = $data['warehouse_id'];
                $inData['destination_warehouse_id'] = $data['destination_warehouse_id'];
                $inData['linked_ledger_id'] = $entry->id;
                
                $this->validateRules($item, $inData);
                $inEntry = Ledger::create($inData);

                // Update the original entry with the link to the new IN entry
                $entry->update(['linked_ledger_id' => $inEntry->id]);

                // For ASSET items, the IN entry will update the location to destination
                if ($item->type === 'ASSET') {
                    $item->update(['current_warehouse_id' => $inData['warehouse_id']]);
                }
            }

            return $entry;
        });
    }

    /**
     * Validate complex dynamic rules.
     */
    protected function validateRules(Item $item, array $data)
    {
        $action = $data['action'];
        $type = $data['type'];

        // Rule: Asset items can only be processed one at a time
        if ($item->type === 'ASSET' && (float)$data['quantity'] !== 1.0) {
            throw new Exception("Asset items can only be processed one at a time (quantity must be 1).");
        }

        // Rule: Asset items must be outside of any warehouse to be logged IN
        // (Unless it's a transfer, but the OUT part of the transfer handles clearing the location first)
        if ($type === 'IN' && $item->type === 'ASSET') {
            if ($item->current_warehouse_id !== null) {
                $currentWH = $item->currentWarehouse ? $item->currentWarehouse->name : "ID: {$item->current_warehouse_id}";
                throw new Exception("Asset '{$item->name}' is already in warehouse '{$currentWH}'. It must be logged out or transferred instead.");
            }
        }

        // Rule: Cannot log OUT if there's no enough stock in the warehouse
        if ($type === 'OUT') {
            $warehouseId = $data['warehouse_id'] ?? null;
            if ($warehouseId) {
                $currentBalance = $item->getBalance($warehouseId);
                $requestedQuantity = $data['quantity'];
                
                if ($currentBalance < $requestedQuantity) {
                    throw new Exception("Cannot perform OUT movement. Available stock for '{$item->name}' is {$currentBalance}, but {$requestedQuantity} was requested.");
                }
            }
        }

        // Rule: TRANSFER requires source and destination and plate number
        if ($action === 'TRANSFER') {
            if (empty($data['source_warehouse_id']) || empty($data['destination_warehouse_id'])) {
                throw new Exception("Transfer action requires both source and destination warehouses.");
            }
            if (empty($data['plate_no'])) {
                throw new Exception("Plate Number is required for transfer movements.");
            }
        }

        // Rule: DELIVERY is always IN
        if ($action === 'DELIVERY' && $type !== 'IN') {
            throw new Exception("Delivery action must be of type IN.");
        }

        // Rule: Mandatory fields for DELIVERY IN (Consumable, Asset and Recoverable)
        if ($type === 'IN' && $action === 'DELIVERY' && in_array($item->type, ['CONSUMABLE', 'ASSET', 'RECOVERABLE'])) {
            if (empty($data['po_number'])) {
                throw new Exception("PO Number is required for item deliveries.");
            }
            if (empty($data['delivery_receipt'])) {
                throw new Exception("Delivery Receipt is required for item deliveries.");
            }
            if (empty($data['plate_no'])) {
                throw new Exception("Plate Number is required for item deliveries.");
            }
        }

        // Rule: DIRECT is for ASSET/RECOVERABLE items logged back in
        if ($action === 'DIRECT') {
            if (!in_array($item->type, ['ASSET', 'RECOVERABLE'])) {
                throw new Exception("Direct action can only be performed on ASSET or RECOVERABLE items.");
            }
            if ($type !== 'IN') {
                throw new Exception("Direct action must be of type IN.");
            }
            if (empty($data['remarks'])) {
                throw new Exception("Remarks are required for direct asset/recoverable log-ins.");
            }
        }

        // Rule: ALLOCATE is for CONSUMABLE items logged out
        if ($action === 'ALLOCATE') {
            if ($item->type !== 'CONSUMABLE') {
                throw new Exception("Allocate action can only be performed on CONSUMABLE items.");
            }
            if ($type !== 'OUT') {
                throw new Exception("Allocate action must be of type OUT.");
            }
            if (empty($data['allocation_id'])) {
                throw new Exception("Allocate action requires an allocation ID.");
            }
        }

        // Rule: MAINTENANCE is for ASSET/RECOVERABLE items logged out
        if ($action === 'MAINTENANCE') {
            if (!in_array($item->type, ['ASSET', 'RECOVERABLE'])) {
                throw new Exception("Maintenance action can only be performed on ASSET or RECOVERABLE items.");
            }
            if ($type !== 'OUT') {
                throw new Exception("Maintenance action must be of type OUT.");
            }
        }

        // Rule: DISPOSE, LOST, RETURN are always OUT
        if (in_array($action, ['DISPOSE', 'LOST', 'RETURN']) && $type !== 'OUT') {
            throw new Exception("{$action} action must be of type OUT.");
        }

        // Rule: Specific OUT actions require remarks
        if (in_array($action, ['DISPOSE', 'LOST', 'MAINTENANCE', 'RETURN']) && empty($data['remarks'])) {
            throw new Exception("Remarks are required for {$action} movements.");
        }

        // Rule: RETURN action requires PO and DR
        if ($type === 'OUT' && $action === 'RETURN') {
            if (empty($data['po_number'])) {
                throw new Exception("PO Number is required for item returns.");
            }
            if (empty($data['delivery_receipt'])) {
                throw new Exception("Delivery Receipt is required for item returns.");
            }
        }
    }
}
