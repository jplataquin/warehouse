<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admins_can_view_the_settings_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
        $response->assertSee('System Settings');
        $response->assertSee('API Settings');
        $response->assertSee(route('api-credentials.index'));
    }

    /** @test */
    public function test_supervisors_cannot_view_the_settings_index()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get(route('admin.settings'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_loggers_cannot_view_the_settings_index()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($logger)->get(route('admin.settings'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_viewers_cannot_view_the_settings_index()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('admin.settings'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_unauthenticated_users_are_redirected_to_login()
    {
        $response = $this->get(route('admin.settings'));

        $response->assertRedirect(route('login'));
    }
}
