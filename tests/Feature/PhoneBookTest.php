<?php

namespace Tests\Feature;

use App\Models\PhoneBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PhoneBookTest extends TestCase
{
    use RefreshDatabase;

    private int $productId;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    public function test_storing_and_deleting_phoneBook(): void
    {
        $response = $this->post('/api/phone-books', [
            'name' => fake()->name,
            'phone_number' => '123456789123',
            'last_settings' => '{}'
        ]);

        $response->assertStatus(201);

        $phoneBook = PhoneBook::first();

        //deleting
        $response = $this->delete('/api/phone-books/' . $phoneBook->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $phoneBook->id]),
        ]);
    }

    public function test_updating_phoneBook() : void
    {
        $phoneBook = PhoneBook::factory()->create(['user_id' => Auth::id()]);

        $response = $this->patch('/api/phone-books/' . $phoneBook->id, [
            'name' => fake()->name,
            'phone_number' => '123456789123',
            'last_settings' => '{}'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $phoneBook->id]),
        ]);
    }

    public function test_fetching_phoneBooks() : void
    {
        PhoneBook::factory()->create(['user_id' => Auth::id()]);

        $response = $this->get('/api/phone-books');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    public function test_checking_phone_number_in_phone_book() : void
    {
        PhoneBook::factory()->create(['user_id' => Auth::id(), 'phone_number' => '123456789123']);

        $response = $this->post('/api/phone-books/check', ['phone_number' => '123456789123']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => 'Phone number is available'
        ]);
    }
}
