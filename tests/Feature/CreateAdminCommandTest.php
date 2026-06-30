<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_admin_user_via_command()
    {
        $this->artisan('admin:create', [
            'email' => 'admin@example.com',
            'password' => 'securepassword123',
        ])
            ->expectsOutput("Admin user 'Admin' (admin@example.com) created successfully.")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('securepassword123', $user->password));
    }

    public function test_can_create_admin_user_with_custom_name()
    {
        $this->artisan('admin:create', [
            'email' => 'custom@example.com',
            'password' => 'securepassword123',
            '--name' => 'Super Administrator',
        ])
            ->expectsOutput("Admin user 'Super Administrator' (custom@example.com) created successfully.")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'custom@example.com',
            'name' => 'Super Administrator',
            'role' => 'admin',
        ]);
    }

    public function test_validation_fails_on_duplicate_email()
    {
        // Seed an existing user
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->artisan('admin:create', [
            'email' => 'existing@example.com',
            'password' => 'securepassword123',
        ])
            ->expectsOutput('The email has already been taken.')
            ->assertExitCode(1);
    }

    public function test_validation_fails_on_invalid_email()
    {
        $this->artisan('admin:create', [
            'email' => 'not-an-email',
            'password' => 'securepassword123',
        ])
            ->expectsOutput('The email field must be a valid email address.')
            ->assertExitCode(1);
    }

    public function test_validation_fails_on_short_password()
    {
        $this->artisan('admin:create', [
            'email' => 'test@example.com',
            'password' => 'short',
        ])
            ->expectsOutput('The password field must be at least 8 characters.')
            ->assertExitCode(1);
    }
}
