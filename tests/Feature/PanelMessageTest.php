<?php

namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\PanelMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PanelMessageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $permission;
    private mixed $role;


    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('panel-message.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }
    /**
     * A basic feature test example.
     */
    public function test_storing_and_deleting_panel_message(): void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/panel-messages', [
            'title' => fake()->title,
            'short_content' => fake()->text(100),
            'body' => fake()->text(550),
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $panelMessage = PanelMessage::first();

        //deleting
        $response = $this->delete('/api/panel-messages/' . $panelMessage->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $panelMessage->id]),
        ]);
    }


    public function test_updating_panel_message() : void
    {
        $this->user->assignRole($this->role);

        $panelMessage = PanelMessage::create([
            'title' => fake()->title,
            'short_content' => fake()->text(100),
            'body' => fake()->text(550),
        ]);

        $response = $this->patch('/api/panel-messages/' . $panelMessage->id, ['items' =>json_encode([
            'title' => fake()->name,
        ])]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $panelMessage->id]),
        ]);
    }


    public function test_fetching_panel_message() : void
    {
        $this->user->assignRole($this->role);

        $panelMessage = PanelMessage::create([
            'title' => fake()->title,
            'short_content' => fake()->text(100),
            'body' => fake()->text(550),
        ]);

        $response = $this->get('/api/panel-messages/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = PanelMessage::first();

        $response2 = $this->get('/api/panel-messages?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
