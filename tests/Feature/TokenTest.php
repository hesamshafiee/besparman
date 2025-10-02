<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create(['type' => User::TYPE_WEBSERVICE]);

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_fetching_tokens() : void
    {
        $response = $this->get('/api/tokens');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_creating_tokens() : void
    {
        $response = $this->get('/api/tokens/create');

        $response->assertStatus(200);
    }

    /**
     * @return void
     */
    public function test_deleting_all_tokens() : void
    {
        $response = $this->delete('/api/tokens');

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => '']),
        ]);
    }

    /**
     * @return void
     */
    public function test_deleting_tokens(): void
    {
        Auth::user()->createToken('web');
        $token = PersonalAccessToken::first();
        $response = $this->delete('/api/tokens/' . $token->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $token->id]),
        ]);
    }
}
