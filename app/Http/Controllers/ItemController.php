<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->latest()->paginate(50)->withQueryString();

        return view('supervisor.items.index', compact('items'));
    }

    public function assets(Request $request)
    {
        $query = Item::where('type', 'ASSET')->with(['currentWarehouse', 'latestUtilizeLedger']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            if ($request->warehouse_id === 'none') {
                $query->whereNull('current_warehouse_id');
            } else {
                $query->where('current_warehouse_id', $request->warehouse_id);
            }
        }

        $assets = $query->get();
        $warehouses = \App\Models\Warehouse::active()->get();

        return view('supervisor.items.assets', compact('assets', 'warehouses'));
    }

    public function create()
    {
        return view('supervisor.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:CONSUMABLE,ASSET',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:Operational,Out of Order',
            'photo_file' => 'nullable|image|max:5120',
            'photo_url' => 'nullable|url',
        ]);

        $exists = Item::withTrashed()
            ->where('name', $validated['name'])
            ->where('specification', $validated['specification'] ?? null)
            ->where('unit', $validated['unit'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'An item with this exact name, specification, and unit already exists.'
            ]);
        }

        $photoPath = $this->processPhoto($request);
        $validated['photo'] = $photoPath;

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function edit(Item $item)
    {
        return view('supervisor.items.edit', compact('item'));
    }

    public function show(Item $item)
    {
        return redirect()->route('items.edit', $item);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'type' => 'required|in:CONSUMABLE,ASSET',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:Operational,Out of Order',
            'photo_file' => 'nullable|image|max:5120',
            'photo_url' => 'nullable|url',
        ]);

        $existingItem = Item::withTrashed()
            ->where('name', $validated['name'])
            ->where('specification', $validated['specification'] ?? null)
            ->where('unit', $validated['unit'])
            ->where('id', '!=', $item->id)
            ->first();

        if ($existingItem) {
            return back()->withInput()->withErrors([
                'name' => "An item with this exact name, specification, and unit already exists. (ID: {$existingItem->id}, Name: {$existingItem->name}, Specification: " . ($existingItem->specification ?? 'N/A') . ", Unit: {$existingItem->unit})"
            ]);
        }

        $validated['is_approved'] = $request->has('is_approved');

        $photoPath = $this->processPhoto($request, $item->photo);
        $validated['photo'] = $photoPath;

        $item->update($validated);

        return redirect()->route('items.index', $request->query())->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to delete items.');
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }

    public function getStock(Request $request, Item $item)
    {
        $warehouseId = $request->query('warehouse_id');
        $balance = $item->getBalance($warehouseId);

        return response()->json([
            'balance' => $balance,
            'unit' => $item->unit,
        ]);
    }

    public function updateStatus(Request $request, Item $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:Operational,Out of Order',
        ]);

        $item->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Asset status updated successfully.');
    }

    public function searchMergeTargets(Request $request, Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $query = $request->query('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $driver = \DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $concatExpr = "name || ' ' || COALESCE(specification, '') || ' ' || unit";
        } else {
            $concatExpr = "CONCAT(name, ' ', COALESCE(specification, ''), ' ', unit)";
        }

        $items = Item::withTrashed()
            ->where('id', '!=', $item->id)
            ->whereRaw("{$concatExpr} LIKE ?", ["%{$query}%"])
            ->limit(20)
            ->get(['id', 'name', 'specification', 'unit', 'type']);

        return response()->json($items);
    }

    public function mergeForm(Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $ledgerCount = \App\Models\Ledger::where('item_id', $item->id)->count();

        return view('supervisor.items.merge', compact('item', 'ledgerCount'));
    }

    public function merge(Request $request, Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $validated = $request->validate([
            'target_item_id' => 'required|exists:items,id|not_in:' . $item->id,
            'confirm_merge' => 'required|accepted',
        ], [
            'target_item_id.required' => 'You must select a target item to merge into.',
            'target_item_id.exists' => 'The selected target item does not exist.',
            'target_item_id.not_in' => 'Cannot merge an item with itself.',
            'confirm_merge.accepted' => 'You must check the confirmation box to proceed.',
            'confirm_merge.required' => 'You must check the confirmation box to proceed.',
        ]);

        $targetItemId = $validated['target_item_id'];

        \DB::transaction(function () use ($item, $targetItemId) {
            // Reassign ledger records
            \App\Models\Ledger::where('item_id', $item->id)->update(['item_id' => $targetItemId]);

            // Reassign asset utilization records
            \App\Models\AssetUtilization::where('item_id', $item->id)->update(['item_id' => $targetItemId]);

            // Soft-delete the source item
            $item->delete();
        });

        return redirect()->route('items.index')->with('success', 'Items consolidated successfully.');
    }

    public function approve(Item $item)
    {
        $item->update(['is_approved' => true]);

        return redirect()->back()->with('success', 'Item "' . $item->name . '" has been approved.');
    }

    public function review(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to review items.');
        }

        $query = Item::where('is_approved', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->latest()->paginate(50)->withQueryString();

        return view('admin.items.review', compact('items'));
    }

    public function loggerCreate(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        return view('logger.items.create', compact('warehouseId'));
    }

    public function loggerStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:CONSUMABLE,ASSET',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:Operational,Out of Order',
            'photo_file' => 'nullable|image|max:5120',
            'photo_url' => 'nullable|url',
            'photo' => 'nullable|string',
        ]);

        $exists = Item::withTrashed()
            ->where('name', $validated['name'])
            ->where('specification', $validated['specification'] ?? null)
            ->where('unit', $validated['unit'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'An item with this exact name, specification, and unit already exists.'
            ]);
        }

        $warehouseId = $request->input('warehouse_id');

        $photoPath = $request->input('photo');
        if (!$photoPath) {
            $photoPath = $this->processPhoto($request);
        }
        $validated['photo'] = $photoPath;

        if (!$request->has('confirm')) {
            $nameSpecUnit = trim($validated['name'] . ' ' . ($validated['specification'] ?? '') . ' ' . $validated['unit']);
            
            $driver = \DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $concatExpr = "name || ' ' || COALESCE(specification, '') || ' ' || unit";
            } else {
                $concatExpr = "CONCAT(name, ' ', COALESCE(specification, ''), ' ', unit)";
            }

            $similarItems = Item::withTrashed()
                ->where(function($q) use ($validated, $nameSpecUnit, $concatExpr) {
                    $q->where('name', 'LIKE', '%' . $validated['name'] . '%')
                      ->orWhereRaw("{$concatExpr} LIKE ?", ["%{$nameSpecUnit}%"]);
                    if (!empty($validated['specification'])) {
                        $q->orWhere('specification', 'LIKE', '%' . $validated['specification'] . '%');
                    }
                })
                ->get();

            if ($similarItems->isNotEmpty()) {
                return view('logger.items.confirm', compact('validated', 'similarItems', 'warehouseId'));
            }
        }

        $validated['is_approved'] = false;
        $item = Item::create($validated);

        if ($warehouseId) {
            return redirect()->route('logger.warehouse.dashboard', $warehouseId)
                ->with('success', 'Item "' . $item->name . '" created successfully and flagged for review.');
        }

        return redirect()->route('home')
            ->with('success', 'Item "' . $item->name . '" created successfully and flagged for review.');
    }

    private function processPhoto(Request $request, $currentPhoto = null)
    {
        if ($request->hasFile('photo_file')) {
            if ($currentPhoto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($currentPhoto);
            }
            return $request->file('photo_file')->store('items', 'public');
        }

        if ($request->filled('photo_url')) {
            try {
                $url = $request->input('photo_url');
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $imageData = $response->body();
                    $extension = 'jpg';
                    $contentType = $response->header('Content-Type');
                    if (str_contains($contentType, 'png')) {
                        $extension = 'png';
                    } elseif (str_contains($contentType, 'gif')) {
                        $extension = 'gif';
                    } elseif (str_contains($contentType, 'webp')) {
                        $extension = 'webp';
                    }
                    $filename = 'items/' . uniqid() . '.' . $extension;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageData);
                    if ($currentPhoto) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($currentPhoto);
                    }
                    return $filename;
                }
            } catch (\Exception $e) {
                \Log::error('Failed to download item photo from URL: ' . $e->getMessage());
            }
        }

        return $currentPhoto;
    }

    public function searchGoogleImages(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:100',
        ]);

        $query = $request->input('query');
        $apiKey = config('services.google_search.api_key');
        $cx = config('services.google_search.search_engine_id');

        if (!$apiKey || !$cx) {
            return response()->json([
                'error' => 'Google Custom Search is not configured. Please add GOOGLE_SEARCH_API_KEY and GOOGLE_SEARCH_ENGINE_ID to your .env file.'
            ], 400);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => $query,
                'searchType' => 'image',
                'num' => 8,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];
                
                $results = array_map(function ($item) {
                    return [
                        'title' => $item['title'] ?? '',
                        'link' => $item['link'] ?? '',
                        'thumbnail' => $item['image']['thumbnailLink'] ?? ($item['link'] ?? ''),
                    ];
                }, $items);

                return response()->json($results);
            }

            return response()->json([
                'error' => 'Failed to retrieve images from Google Custom Search API: ' . $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while connecting to the image search service: ' . $e->getMessage()
            ], 500);
        }
    }

    public function similar(Item $item)
    {
        $concatenatedQuery = trim($item->name . ' ' . ($item->specification ?? ''));

        $driver = \DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $concatExpr = "name || ' ' || COALESCE(specification, '')";
        } else {
            $concatExpr = "CONCAT(name, ' ', COALESCE(specification, ''))";
        }

        $similarItems = Item::withTrashed()
            ->select('items.*')
            ->selectRaw("{$concatExpr} as item_name")
            ->where('id', '!=', $item->id)
            ->whereRaw("{$concatExpr} LIKE ?", ["%{$concatenatedQuery}%"])
            ->orderByRaw("{$concatExpr} ASC")
            ->limit(10)
            ->get();

        return view('supervisor.items._similar', compact('similarItems', 'item'));
    }
}
