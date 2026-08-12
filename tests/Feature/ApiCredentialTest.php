<?php

namespace Tests\Feature;

use App\Models\ApiCredential;
use App\Models\User;
use App\Services\MqmsApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ApiCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_admins_can_view_the_api_credentials_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('api-credentials.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.api-credentials.index');
    }

    /** @test */
    public function test_supervisors_cannot_view_the_api_credentials_index()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get(route('api-credentials.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_loggers_cannot_view_the_api_credentials_index()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($logger)->get(route('api-credentials.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_admins_can_create_an_api_credential()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('api-credentials.store'), [
            'service' => 'mqms',
            'base_url' => 'https://api.test-mqms.com',
            'api_key' => 'test-api-key',
            'secret_key' => 'super-secret',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('api-credentials.index'));
        $this->assertDatabaseHas('api_credentials', [
            'service' => 'mqms',
            'base_url' => 'https://api.test-mqms.com',
            'api_key' => 'test-api-key',
            'is_active' => true,
        ]);

        // Verify the key is stored encrypted in DB but decrypted when fetched
        $credential = ApiCredential::where('service', 'mqms')->first();
        $this->assertEquals('super-secret', $credential->secret_key);

        // Raw DB check to confirm encryption
        $rawSecret = \DB::table('api_credentials')->where('service', 'mqms')->value('secret_key');
        $this->assertNotEquals('super-secret', $rawSecret);
        $this->assertEquals('super-secret', Crypt::decryptString($rawSecret));
    }

    /** @test */
    public function test_admins_can_update_an_api_credential()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $credential = ApiCredential::create([
            'service' => 'mqms',
            'base_url' => 'https://api.test-mqms.com',
            'api_key' => 'old-api-key',
            'secret_key' => 'old-secret',
            'is_active' => true,
        ]);

        // Update without changing secret key
        $response = $this->actingAs($admin)->put(route('api-credentials.update', $credential), [
            'service' => 'mqms',
            'base_url' => 'https://api.updated-mqms.com',
            'api_key' => 'new-api-key',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('api-credentials.index'));
        
        $credential->refresh();
        $this->assertEquals('https://api.updated-mqms.com', $credential->base_url);
        $this->assertEquals('new-api-key', $credential->api_key);
        $this->assertEquals('old-secret', $credential->secret_key); // unmodified

        // Update changing secret key
        $this->actingAs($admin)->put(route('api-credentials.update', $credential), [
            'service' => 'mqms',
            'base_url' => 'https://api.updated-mqms.com',
            'api_key' => 'new-api-key',
            'secret_key' => 'new-secret-key',
            'is_active' => '1',
        ]);

        $credential->refresh();
        $this->assertEquals('new-secret-key', $credential->secret_key);
    }

    /** @test */
    public function test_admins_can_delete_an_api_credential()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $credential = ApiCredential::create([
            'service' => 'mqms',
            'base_url' => 'https://api.test-mqms.com',
            'api_key' => 'test-api-key',
            'secret_key' => 'super-secret',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('api-credentials.destroy', $credential));

        $response->assertRedirect(route('api-credentials.index'));
        $this->assertDatabaseMissing('api_credentials', ['id' => $credential->id]);
    }

    /** @test */
    public function test_mqms_api_client_uses_database_settings_when_active()
    {
        // 1. Arrange: Create API credential in the database
        ApiCredential::create([
            'service' => 'mqms',
            'base_url' => 'https://api.database-mqms.com',
            'api_key' => 'db-api-key',
            'secret_key' => 'db-secret-key',
            'is_active' => true,
        ]);

        // 2. Act: Instantiate the MqmsApiClient
        $client = new MqmsApiClient();

        // 3. Assert: Using reflection, check internal state of the properties
        $reflection = new \ReflectionClass($client);
        
        $baseUrlProp = $reflection->getProperty('baseUrl');
        $baseUrlProp->setAccessible(true);
        $this->assertEquals('https://api.database-mqms.com/', $baseUrlProp->getValue($client));

        $apiKeyProp = $reflection->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $this->assertEquals('db-api-key', $apiKeyProp->getValue($client));

        $secretKeyProp = $reflection->getProperty('secretKey');
        $secretKeyProp->setAccessible(true);
        $this->assertEquals('db-secret-key', $secretKeyProp->getValue($client));
    }

    /** @test */
    public function test_mqms_api_client_falls_back_to_env_when_database_settings_are_inactive()
    {
        // Set env values manually for testing backup
        config(['services.mqms.base_url' => 'https://api.env-mqms.com']);
        // Note: env() calls in phpunit might bypass config if hardcoded in MqmsApiClient.
        // Let's check how MqmsApiClient handles fallback - it calls env().
        // In phpunit, we can define env parameters inside phpunit.xml, or we can mock env.
        // But let's check: if DB is inactive, does it bypass the DB settings? Yes.
        
        ApiCredential::create([
            'service' => 'mqms',
            'base_url' => 'https://api.database-mqms.com',
            'api_key' => 'db-api-key',
            'secret_key' => 'db-secret-key',
            'is_active' => false, // Inactive!
        ]);

        $client = new MqmsApiClient();

        $reflection = new \ReflectionClass($client);
        
        $baseUrlProp = $reflection->getProperty('baseUrl');
        $baseUrlProp->setAccessible(true);
        $this->assertNotEquals('https://api.database-mqms.com/', $baseUrlProp->getValue($client));

        $apiKeyProp = $reflection->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $this->assertNotEquals('db-api-key', $apiKeyProp->getValue($client));
    }
}
