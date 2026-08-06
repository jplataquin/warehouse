@if($similarItems->isEmpty())
    <div class="alert alert-info py-2 mb-0 border-0">
        <i class="bi bi-info-circle me-1"></i> No similar items found in the database.
    </div>
@else
    <div class="table-responsive bg-light p-3 rounded shadow-inner" style="background-color: #fafbfc !important; border: 1px inset rgba(0,0,0,0.05);">
        <h6 class="fw-bold mb-3 text-secondary" style="font-size: 0.85rem;"><i class="bi bi-layers-half me-1"></i> Similar Items in Database:</h6>
        <table class="table table-sm table-hover align-middle mb-0 bg-white shadow-sm rounded" style="font-size: 0.85rem;">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3 py-2">Item Details</th>
                    <th class="py-2">Type</th>
                    <th class="py-2">Unit</th>
                    <th class="py-2">Status</th>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                        <th class="text-end pe-3 py-2">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($similarItems as $sItem)
                <tr>
                    <td class="ps-3 py-2">
                        <div class="fw-bold text-dark d-flex align-items-center gap-1">
                            <span>{{ $sItem->name }}</span>
                        </div>
                        @if($sItem->specification)
                            <div class="small text-muted">{{ $sItem->specification }}</div>
                        @endif
                    </td>
                    <td class="py-2">
                        <span class="badge bg-light text-dark border">{{ $sItem->type }}</span>
                    </td>
                    <td class="py-2">{{ $sItem->unit }}</td>
                    <td class="py-2">
                        <span class="badge {{ $sItem->status === 'Operational' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} px-2 py-1">
                            {{ $sItem->status }}
                        </span>
                    </td>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                        <td class="text-end pe-3 py-2">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('items.edit', $sItem) }}" class="btn btn-xs btn-outline-secondary py-0 px-2 fw-bold" style="font-size: 0.75rem;" title="Edit this item">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                @if(auth()->user()->isAdmin())
                                    @php
                                        $specStr = $sItem->specification ? " ({$sItem->specification})" : "";
                                        $targetName = "ID: {$sItem->id} - {$sItem->name}{$specStr} - {$sItem->unit}";
                                    @endphp
                                    <a href="{{ route('items.merge.form', [
                                        'item' => $item->id,
                                        'target_item_id' => $sItem->id,
                                        'target_item_name' => $targetName
                                    ]) }}" class="btn btn-xs btn-outline-warning py-0 px-2 fw-bold" style="font-size: 0.75rem;" title="Merge pending item INTO this existing item">
                                        <i class="bi bi-arrow-left-right me-1"></i> Merge
                                    </a>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
