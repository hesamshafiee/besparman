<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

        Storage::fake('public');

        $this->user = User::factory()->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('product.*');

        Sanctum::actingAs($this->user, ['*']);
    }

    private function fakePng(string $name = 'img.png'): UploadedFile
    {
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axhZV8AAAAASUVORK5CYII=';
        return UploadedFile::fake()->createWithContent($name, base64_decode($base64));
    }

    /**
     * Client: create & delete
     */
    public function test_client_storing_and_deleting_product(): void
    {
        $category = Category::factory()->create();
        $variant  = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TST-SKU-1',
            'stock'       => 10,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $w1 = Work::create([
            'user_id'       => $this->user->id,
            'title'         => 'Alpha',
            'slug'          => Work::makeSlug('Alpha'),
            'description'   => 'desc A',
            'is_published'  => true,
            'published_at'  => now(),
        ]);

        $payload = [
            'name'       => 'تیشرت طرح گربه',
            'price'      => 250000,
            'type'       => 'standard',
            'variant_id' => $variant->id,
            'status'     => 1,
            'work_id'    => $w1->id,
            'user_id'    => $this->user->id,
            'settings'   => json_encode([
                'fit_mode' => 'contain',
                'rotation' => 0,
            ]),
        ];

        $res = $this->post('/api/clients/products', $payload);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('user_id', $this->user->id)->first();
        $this->assertNotNull($product);

        $del = $this->delete('/api/clients/products/' . $product->id);
        $del->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
    }

    /**
     * Client: update
     */
    public function test_client_updating_product(): void
    {
        $category = Category::factory()->create();
        $variant  = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TST-SKU-2',
            'stock'       => 5,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $w1 = Work::create([
            'user_id'       => $this->user->id,
            'title'         => 'Alpha',
            'slug'          => Work::makeSlug('Alpha'),
            'description'   => 'desc A',
            'is_published'  => true,
            'published_at'  => now(),
        ]);

        $product = Product::create([
            'user_id'    => $this->user->id,
            'work_id'    => $w1->id,
            'variant_id' => $variant->id,
            'name'       => 'طرح قدیمی',
            'slug'       => 'old-slug-1234',
            'price'      => 150000,
            'currency'   => 'IRR',
            'type'       => 'standard',
            'status'     => 1,
        ]);

        $update = [
            'name'       => 'طرح جدید',
            'price'      => 199000,
            'status'     => 1,
            'user_id'    => $this->user->id,
            'work_id'    => $w1->id,
            'variant_id' => $variant->id,
            'slug'       => '',
        ];

        $res = $this->put('/api/clients/products/' . $product->id, $update);

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

    /**
     * Client: listing & single
     */
    public function test_client_fetching_products(): void
    {
        $category = Category::factory()->create();
        $variant  = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TST-SKU-3',
            'stock'       => 8,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $w1 = Work::create([
            'user_id'       => $this->user->id,
            'title'         => 'Alpha',
            'slug'          => Work::makeSlug('Alpha'),
            'description'   => 'desc A',
            'is_published'  => true,
            'published_at'  => now(),
        ]);

        Product::create([
            'user_id'    => $this->user->id,
            'variant_id' => $variant->id,
            'name'       => 'محصول ۱',
            'work_id'    => $w1->id,
            'slug'       => 'product-1-' . \Str::random(4),
            'price'      => 120000,
            'currency'   => 'IRR',
            'type'       => 'standard',
            'status'     => 1,
        ]);

        $res = $this->get('/api/clients/products?per_page=2');

        $res->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $first = Product::where('user_id', $this->user->id)->first();

        $res2 = $this->get('/api/clients/products?id=' . $first->id);

        $res2->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * Admin: CRUD + restore
     */
    public function test_admin_crud_and_restore(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();
        $variant  = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TST-SKU-4',
            'stock'       => 3,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $w1 = Work::create([
            'user_id'       => $this->user->id,
            'title'         => 'Alpha',
            'slug'          => Work::makeSlug('Alpha'),
            'description'   => 'desc A',
            'is_published'  => true,
            'published_at'  => now(),
        ]);

        $payload = [
            'user_id'    => $this->user->id,
            'variant_id' => $variant->id,
            'name'       => 'هودی مشکی',
            'price'      => 350000,
            'type'       => 'standard',
            'status'     => 1,
            'work_id'    => $w1->id,
        ];

        $create = $this->post('/api/products', $payload);

        $create->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('name', 'هودی مشکی')->firstOrFail();

        $list = $this->get('/api/products?per_page=1');
        $list->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $single = $this->get('/api/products?id=' . $product->id);
        $single->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );

        $update = $this->put('/api/products/' . $product->id, [
            'name'       => 'هودی سرمه‌ای',
            'price'      => 360000,
            'work_id'    => $w1->id,
            'user_id'    => $this->user->id,
            'variant_id' => $variant->id,
        ]);

        $update->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $product->id]),
        ]);

        $this->assertDatabaseHas('products', [
            'id'        => $product->id,
            'name'      => 'هودی سرمه‌ای',
            'price'     => 360000,
            'work_id'   => $w1->id,
            'user_id'   => $this->user->id,
            'variant_id'=> $variant->id,
        ]);

        $delete = $this->delete('/api/products/' . $product->id);
        $delete->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $restore = $this->post('/api/products/' . $product->id . '/restore');
        $restore->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.restoredSuccessfully'),
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    /**
     * Client bulk store: global image + per-variant settings
     */
    public function test_client_bulk_store_with_global_image_and_per_variant_settings(): void
    {
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();

        $v1 = Variant::create([
            'category_id' => $c1->id,
            'sku'         => 'BULK-SKU-1',
            'stock'       => 10,
            'add_price'   => 0,
            'is_active'   => true,
        ]);
        $v2 = Variant::create([
            'category_id' => $c2->id,
            'sku'         => 'BULK-SKU-2',
            'stock'       => 5,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $work = Work::factory()->create(['user_id' => $this->user->id]);

        $globalSettings = ['fit' => 'contain', 'offset' => ['x' => 0, 'y' => 0]];
        $v1Settings     = ['fit' => 'cover',   'scale'  => 1.1];
        $v2Settings     = ['fit' => 'contain', 'scale'  => 0.9];

        $payload = [
            'work_id'  => $work->id,
            'name'     => 'طرح مشترک',
            'price'    => 150000,
            'status'   => 1,
            'sku'      => 'TS-2025',
            'settings' => json_encode($globalSettings),

            'image'    => $this->fakePng('common.png'),

            'variants' => [
                [
                    'variant_id' => $v1->id,
                    'address'    => 'node:12',
                    'settings'   => json_encode($v1Settings),
                ],
                [
                    'variant_id' => $v2->id,
                    'price'      => 180000,
                    'settings'   => json_encode($v2Settings),
                ],
            ],
        ];

        $res = $this->post('/api/clients/products/bulk', $payload);

        $res->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => __('general.savedSuccessfully'),
                'data'    => ['work_id' => $work->id, 'count' => 2],
            ]);

        $products = Product::where('user_id', $this->user->id)
            ->where('work_id', $work->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $products);

        $p1 = $products[0];
        $p2 = $products[1];

        $this->assertSame($work->id, $p1->work_id);
        $this->assertSame($v1->id,   $p1->variant_id);
        $this->assertSame(150000,    (int) $p1->price);
        $this->assertSame(1,         (int) $p1->status);
        $this->assertNotEmpty($p1->original_path);
        $this->assertNotEmpty($p1->preview_path);
        $this->assertIsArray($p1->settings);
        $this->assertSame('cover',   $p1->settings['fit']);
        $this->assertSame(1.1,       (float) $p1->settings['scale']);

        $this->assertSame($work->id, $p2->work_id);
        $this->assertSame($v2->id,   $p2->variant_id);
        $this->assertSame(180000,    (int) $p2->price);
        $this->assertSame(1,         (int) $p2->status);
        $this->assertNotEmpty($p2->original_path);
        $this->assertNotEmpty($p2->preview_path);
        $this->assertIsArray($p2->settings);
        $this->assertSame('contain', $p2->settings['fit']);
        $this->assertSame(0.9,       (float) $p2->settings['scale']);
    }

    public function test_client_bulk_store_requires_images_when_no_global_and_some_missing(): void
    {
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();

        $v1 = Variant::create([
            'category_id' => $c1->id,
            'sku'         => 'BULK-SKU-3',
            'stock'       => 5,
            'add_price'   => 0,
            'is_active'   => true,
        ]);
        $v2 = Variant::create([
            'category_id' => $c2->id,
            'sku'         => 'BULK-SKU-4',
            'stock'       => 7,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $work = Work::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'work_id'  => $work->id,
            'name'     => 'بدون تصویر عمومی',
            'price'    => 1000,
            'variants' => [
                [
                    'variant_id' => $v1->id,
                    'image'      => $this->fakePng('v1.jpg'),
                ],
                [
                    'variant_id' => $v2->id,
                ],
            ],
        ];

        $res = $this->post('/api/clients/products/bulk', $payload);

        $res->assertStatus(422);
    }

    public function test_client_bulk_store_with_per_variant_images_only(): void
    {
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();

        $v1 = Variant::create([
            'category_id' => $c1->id,
            'sku'         => 'BULK-SKU-5',
            'stock'       => 8,
            'add_price'   => 0,
            'is_active'   => true,
        ]);
        $v2 = Variant::create([
            'category_id' => $c2->id,
            'sku'         => 'BULK-SKU-6',
            'stock'       => 4,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $work = Work::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'work_id'  => $work->id,
            'name'     => 'تصاویر اختصاصی واریانت‌ها',
            'price'    => 220000,
            'status'   => 1,
            'variants' => [
                [
                    'variant_id' => $v1->id,
                    'image'      => $this->fakePng('v1.png'),
                    'settings'   => json_encode(['fit' => 'cover']),
                ],
                [
                    'variant_id' => $v2->id,
                    'image'      => $this->fakePng('v2.png'),
                    'settings'   => json_encode(['fit' => 'contain']),
                ],
            ],
        ];

        $res = $this->post('/api/clients/products/bulk', $payload);

        $res->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => __('general.savedSuccessfully'),
                'data'    => ['work_id' => $work->id, 'count' => 2],
            ]);

        $this->assertDatabaseCount('products', 2);

        $ps = Product::where('work_id', $work->id)->orderBy('id')->get();

        $this->assertSame($v1->id, $ps[0]->variant_id);
        $this->assertSame('cover', $ps[0]->settings['fit']);

        $this->assertSame($v2->id, $ps[1]->variant_id);
        $this->assertSame('contain', $ps[1]->settings['fit']);
    }
}
