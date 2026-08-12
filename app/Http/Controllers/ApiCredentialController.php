<?php

namespace App\Http\Controllers;

use App\Models\ApiCredential;
use Illuminate\Http\Request;

class ApiCredentialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $credentials = ApiCredential::orderBy('service')->get();
        return view('admin.api-credentials.index', compact('credentials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.api-credentials.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|string|unique:api_credentials,service|max:255',
            'base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ApiCredential::create($validated);

        return redirect()->route('api-credentials.index')
            ->with('success', 'API credential created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ApiCredential $apiCredential)
    {
        return redirect()->route('api-credentials.edit', $apiCredential);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApiCredential $apiCredential)
    {
        return view('admin.api-credentials.edit', compact('apiCredential'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApiCredential $apiCredential)
    {
        $validated = $request->validate([
            'service' => 'required|string|max:255|unique:api_credentials,service,' . $apiCredential->id,
            'base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Only update secret_key if a new one is provided, or if they explicitly want to clear it
        if ($request->filled('secret_key')) {
            $validated['secret_key'] = $request->input('secret_key');
        } elseif ($request->has('clear_secret_key')) {
            $validated['secret_key'] = null;
        } else {
            unset($validated['secret_key']);
        }

        $apiCredential->update($validated);

        return redirect()->route('api-credentials.index')
            ->with('success', 'API credential updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApiCredential $apiCredential)
    {
        $apiCredential->delete();

        return redirect()->route('api-credentials.index')
            ->with('success', 'API credential deleted successfully.');
    }
}
