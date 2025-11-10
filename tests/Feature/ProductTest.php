<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('product.*');

        Sanctum::actingAs($this->user, ['*']);
    }

    /** ----------------- Client: create & delete ----------------- */
    public function test_client_storing_and_deleting_product(): void
    {
        $category = Category::factory()->create();

        $payload = [
            'name'        => 'تیشرت طرح گربه',
            'price'       => 250000,
            'type'        => 'standard',
            'category_id' => $category->id,
            'status'      => 1,
            'settings'    => json_encode(['fit_mode' => 'contain', 'rotation' => 0]),
        ];

        $res = $this->post('/api/v1/client/products', $payload);
        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('user_id', $this->user->id)->first();
        $this->assertNotNull($product);

        $del = $this->delete('/api/v1/client/products/' . $product->id);
        $del->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
    }

    /** ----------------- Client: update ----------------- */
    public function test_client_updating_product(): void
    {
        $category = Category::factory()->create();

        $product = Product::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'name'        => 'طرح قدیمی',
            'slug'        => 'old-slug-1234',
            'price'       => 150000,
            'currency'    => 'IRR',
            'type'        => 'standard',
            'status'      => 1,
        ]);

        $update = [
            'name'  => 'طرح جدید',
            'price' => 199000,
            'slug'  => '',
        ];

        $res = $this->put('/api/v1/client/products/' . $product->id, $update);
        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $product->id]),
        ]);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'طرح جدید',
            'price' => 199000,
        ]);
    }

    /** ----------------- Client: listing & single ----------------- */
    public function test_client_fetching_products(): void
    {
        $category = Category::factory()->create();

        Product::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'name'        => 'محصول ۱',
            'slug'        => 'product-1-'.\Str::random(4),
            'price'       => 120000,
            'currency'    => 'IRR',
            'type'        => 'standard',
            'status'      => 1,
        ]);

        $res = $this->get('/api/v1/client/products?per_page=2');
        $res->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $first = Product::where('user_id', $this->user->id)->first();

        $res2 = $this->get('/api/v1/client/products?id=' . $first->id);
        $res2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /** ----------------- Admin: CRUD + restore ----------------- */
    public function test_admin_crud_and_restore(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $createPayload = [
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'name'        => 'هودی مشکی',
            'price'       => 350000,
            'type'        => 'standard',
            'status'      => 1,
        ];
        $create = $this->post('/api/v1/products', $createPayload);
        $create->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('name', 'هودی مشکی')->firstOrFail();

        $list = $this->get('/api/v1/products?per_page=1');
        $list->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $single = $this->get('/api/v1/products?id=' . $product->id);
        $single->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );

        $update = $this->put('/api/v1/products/' . $product->id, [
            'name'  => 'هودی سرمه‌ای',
            'price' => 360000,
        ]);
        $update->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $product->id]),
        ]);
        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'هودی سرمه‌ای',
            'price' => 360000,
        ]);

        $delete = $this->delete('/api/v1/products/' . $product->id);
        $delete->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $restore = $this->post('/api/v1/products/' . $product->id . '/restore');
        $restore->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.restoredSuccessfully'),
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }
}
