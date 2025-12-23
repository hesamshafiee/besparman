<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private int $productId;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('comment.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $this->productId = Product::factory()->create()->id;
    }

    /**
     * @return void
     */
    public function test_storing_and_changing_status_comment(): void
    {
        $response = $this->post('/api/comments', [
            'comment' => 'test test test test',
            'model' => 'product',
            'id' => $this->productId
        ]);

        $response->assertStatus(200);

        $comment = Comment::first();

        //changing status
        $response = $this->patch('/api/comments/status/' . $comment->id);

        $response->assertStatus(200);
    }

    /**
     * @return void
     */
    public function test_updating_comment() : void
    {
        $this->post('/api/comments', [
            'comment' => 'test test test test',
            'model' => 'product',
            'id' => $this->productId
        ]);


        $comment = Comment::first();

        $response = $this->patch('/api/comments/' . $comment->id, [
            'comment' => 'test test test test',
        ]);

        $response->assertStatus(200);
    }

    /**
     * @return void
     */
    public function test_fetching_comments() : void
    {
        $this->post('/api/comments', [
            'comment' => 'test test test test',
            'model' => 'product',
            'id' => $this->productId
        ]);

        $response = $this->get('/api/comments');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Comment::first();

        $response2 = $this->get('/api/comments?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
