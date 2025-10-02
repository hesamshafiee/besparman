<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    public function test_storing_and_fetching_and_closing_tickets(): void
    {
        $response = $this->post('/api/tickets/', [
            'title' => fake()->title,
            'message' => fake()->title,
            'category' => 'account'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $ticket = Ticket::first();

        $this->get('/api/tickets/by-user')->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $this->get('/api/tickets/conversation/' . $ticket->id)->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $this->get('/api/tickets/close/' . $ticket->id)->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }
}
