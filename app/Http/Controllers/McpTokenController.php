<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class McpTokenController extends Controller
{
    /**
     * Get or create the dedicated MCP service account.
     */
    protected function getServiceAccount(): User
    {
        return User::firstOrCreate(
            ['email' => 'mcp-service@system.local'],
            [
                'name' => 'MCP Service Account',
                'password' => bcrypt(Str::random(32)),
                'role' => 'admin',
                'needs_password_change' => false,
            ]
        );
    }

    /**
     * Display a listing of the MCP tokens.
     */
    public function index()
    {
        $serviceAccount = $this->getServiceAccount();
        $tokens = $serviceAccount->tokens()->latest()->get();

        return view('admin.mcp-tokens.index', compact('tokens'));
    }

    /**
     * Store a newly created token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $serviceAccount = $this->getServiceAccount();
        $token = $serviceAccount->createToken($request->token_name, ['mcp-access']);

        return redirect()->route('mcp-tokens.index')
            ->with('success', 'MCP Access Token generated successfully.')
            ->with('new_token', $token->plainTextToken);
    }

    /**
     * Remove the specified token.
     */
    public function destroy($id)
    {
        $serviceAccount = $this->getServiceAccount();
        $serviceAccount->tokens()->where('id', $id)->delete();

        return redirect()->route('mcp-tokens.index')
            ->with('success', 'MCP Access Token revoked successfully.');
    }
}
