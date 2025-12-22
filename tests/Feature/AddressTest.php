<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Address; // Import Address model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use function PHPUnit\Framework\assertNotTrue;
use function PHPUnit\Framework\assertTrue;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $role;
    private $adminUser;
    private $clientUser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('address.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    
    public function test_storing_and_deleting_addresses_admin() : void
    {
        $this->user->assignRole($this->role);
        $addressData = [
            'user_id' => $this->user->id,
            'title' => 'Home Address',
            'province' => 'Tehran', 
            'city' => 'Tehran', 
            'address' => '123 Main St, Apt 4B', 
            'postal_code' => '12345-6789', 
            'phone' => '02112345678', 
            'mobile' => '09121234567', 
            'is_default' => true, 
        ];

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $Address = Address::where('user_id', operator: $this->user->id)->first();

        $response = $this->delete('/api/addresses/' . $Address->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $Address->id]),
        ]);

    }


    public function test_updating_addresses_admin() : void
    {
        $this->user->assignRole($this->role);

        $addressData = [
            'user_id' => $this->user->id,
            'title' => 'Home Address',
            'province' => 'Tehran', 
            'city' => 'Tehran', 
            'address' => '123 Main St, Apt 4B', 
            'postal_code' => '12345-6789', 
            'phone' => '02112345678', 
            'mobile' => '09121234567', 
            'is_default' => true, 
        ];

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);


    }


    public function test_fetching_addresses_admin() : void
    {
        $this->user->assignRole($this->role);

        $addressData = [
            'user_id' => $this->user->id,
            'title' => 'Home Address',
            'province' => 'Tehran', 
            'city' => 'Tehran', 
            'address' => '123 Main St, Apt 4B', 
            'postal_code' => '12345-6789', 
            'phone' => '02112345678', 
            'mobile' => '09121234567', 
            'is_default' => true, 
        ];

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $response = $this->get('/api/addresses');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );


    }



    public function test_storing_and_deleting_addresses_client() : void
    {
        $addressData = [
            'title' => 'Home Address', // Using 'title' from model
            'province' => 'Tehran', // Using 'province' from model
            'city' => 'Tehran', // Using 'city' from model
            'address' => '123 Main St, Apt 4B', // Using 'address' from model
            'postal_code' => '12345-6789', // Using 'postal_code' from model
            'phone' => '02112345678', // Using 'phone' from model
            'mobile' => '09121234567', // Using 'mobile' from model
            'is_default' => true, // Using 'is_default' from model (boolean)
        ];

        $response = $this->postJson('/api/clients/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $Address = Address::where('user_id', operator: $this->user->id)->first();


        $response = $this->delete('/api/clients/addresses/' . $Address->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $Address->id]),
        ]);
       
    }


    public function test_updating_addresses_client() : void
    {
        $addressData = [
            'user_id' => $this->user->id,
            'title' => 'Home Address',
            'province' => 'Tehran', 
            'city' => 'Tehran', 
            'address' => '123 Main St, Apt 4B', 
            'postal_code' => '12345-6789', 
            'phone' => '02112345678', 
            'mobile' => '09121234567', 
            'is_default' => true, 
        ];

        $response = $this->postJson('/api/clients/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $Address = Address::where('user_id', operator: $this->user->id)->first();
        
        $response = $this->patch('/api/clients/addresses/' . $Address->id, ['items' =>json_encode([
            'title' => 'office address', // Using 'title' from model
        ])]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $Address->id]),
        ]);
    }


    public function test_fetching_addresses_client(): void
    {
        $addressData = [
            'user_id' => $this->user->id,
            'title' => 'Home Address',
            'province' => 'Tehran', 
            'city' => 'Tehran', 
            'address' => '123 Main St, Apt 4B', 
            'postal_code' => '12345-6789', 
            'phone' => '02112345678', 
            'mobile' => '09121234567', 
            'is_default' => true, 
        ];

        $response = $this->postJson('/api/clients/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $response = $this->get('/api/clients/addresses');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
}
