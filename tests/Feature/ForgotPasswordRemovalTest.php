<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForgotPasswordRemovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the login page does not contain the "Forgot Your Password?" link.
     */
    public function test_login_page_does_not_contain_forgot_password_link(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Forgot Your Password?');
        $response->assertDontSee('/password/reset');
    }

    /**
     * Test that password reset routes are completely disabled.
     */
    public function test_password_reset_routes_are_disabled(): void
    {
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.email'));
        $this->assertFalse(Route::has('password.reset'));
        $this->assertFalse(Route::has('password.update'));

        // Direct GET request to reset route should return 404 since it's not registered
        $response = $this->get('/password/reset');
        $response->assertStatus(404);

        // Direct POST request to email route should return 404 since it's not registered
        $response = $this->post('/password/email');
        $response->assertStatus(404);
    }
}
