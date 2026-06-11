<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user is redirected to password change route if they need a password change.
     */
    public function test_user_is_redirected_to_password_change_route()
    {
        $user = User::factory()->create([
            'needs_password_change' => true,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('password.change'));
        $response->assertSessionHas('error', 'You must change your password before proceeding.');
    }

    /**
     * Test that user can access password change view even if they need password change.
     */
    public function test_user_can_access_password_change_view()
    {
        $user = User::factory()->create([
            'needs_password_change' => true,
        ]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertStatus(200);
        $response->assertSee('Create New Password');
        $response->assertSee('New Password');
    }

    /**
     * Test that user cannot bypass password change and accessing other routes is blocked.
     */
    public function test_user_cannot_bypass_password_change()
    {
        $user = User::factory()->create([
            'needs_password_change' => true,
        ]);

        $response = $this->actingAs($user)->get(route('global.search'));

        $response->assertRedirect(route('password.change'));
    }

    /**
     * Test password change validation.
     */
    public function test_password_change_validation()
    {
        $user = User::factory()->create([
            'needs_password_change' => true,
        ]);

        // Submit matching but too short password
        $response = $this->actingAs($user)->post(route('password.change.update'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue($user->fresh()->needs_password_change);

        // Submit non-matching confirmation
        $response = $this->actingAs($user)->post(route('password.change.update'), [
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue($user->fresh()->needs_password_change);
    }

    /**
     * Test successful password change.
     */
    public function test_successful_password_change()
    {
        $user = User::factory()->create([
            'needs_password_change' => true,
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user)->post(route('password.change.update'), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success', 'Your password has been changed successfully.');

        $user = $user->fresh();
        $this->assertFalse($user->needs_password_change);
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Attempting to access home now should succeed
        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
    }

    /**
     * Test that admin-created users are saved with needs_password_change set to true.
     */
    public function test_admin_created_users_need_password_change()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $userData = [
            'name' => 'Newly Created User',
            'email' => 'newuser@warehouse.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'supervisor',
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'User created successfully.');

        $createdUser = User::where('email', 'newuser@warehouse.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->needs_password_change);
    }
}
