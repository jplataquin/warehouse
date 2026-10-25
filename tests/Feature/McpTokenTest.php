<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class McpTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_admin_cannot_access_mcp_tokens(): void
    {
        $this->get(route('mcp-tokens.index'))->assertRedirect(route('login'));

        $logger = User::factory()->create(['role' => 'logger']);
        $this->actingAs($logger)->get(route('mcp-tokens.index'))->assertStatus(403);
    }

    public function test_admin_can_view_mcp_tokens_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('mcp-tokens.index'));

        $response->assertOk();
        $response->assertSee('MCP Access Tokens');
        $this->assertDatabaseHas('users', [
            'email' => 'mcp-service@system.local',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_generate_mcp_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('mcp-tokens.store'), [
            'token_name' => 'Claude Desktop App',
        ]);

        $response->assertRedirect(route('mcp-tokens.index'));
        $response->assertSessionHas('new_token');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Claude Desktop App',
        ]);
    }

    public function test_generated_token_can_authenticate_against_mcp_server(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('mcp-tokens.store'), [
            'token_name' => 'Gemini Assistant',
        ]);

        $tokenString = session('new_token');
        $this->assertNotNull($tokenString);

        // Test the MCP Server accepts this token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenString,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/list',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertOk();
    }

    public function test_admin_can_revoke_mcp_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('mcp-tokens.store'), [
            'token_name' => 'Temporary Key',
        ]);

        $token = PersonalAccessToken::where('name', 'Temporary Key')->first();
        $this->assertNotNull($token);

        $response = $this->actingAs($admin)->delete(route('mcp-tokens.destroy', $token->id));

        $response->assertRedirect(route('mcp-tokens.index'));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }
}
