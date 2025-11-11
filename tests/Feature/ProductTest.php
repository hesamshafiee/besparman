<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
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

        // مایگریشن و سیدها
        $this->artisan('migrate');
        $this->artisan('db:seed');


        Storage::fake('public'); // ← اضافه شد

        // کاربر لاگین
        $this->user = User::factory()->create();

        // نقش ادمین تستی
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('product.*'); // فرض: permission در پروژه‌ات ساخته می‌شود

        // احراز هویت
        Sanctum::actingAs($this->user, ['*']);
    }
        private function fakePng(string $name = 'img.png'): UploadedFile
    {
        // PNG 1x1 شفاف
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axhZV8AAAAASUVORK5CYII=';
        return UploadedFile::fake()->createWithContent($name, base64_decode($base64));
    }

    /** ----------------- Client: create & delete ----------------- */
    public function test_client_storing_and_deleting_product(): void
    {
        $category = Category::factory()->create();

        $w1 = Work::create([
            'user_id' => $this->user->id,
            'title' => 'Alpha',
            'slug' => Work::makeSlug('Alpha'),
            'description' => 'desc A',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $payload = [
            'name'        => 'تیشرت طرح گربه',
            'price'       => 250000,
            'type'        => 'standard',
            'category_id' => $category->id,
            'status'      => 1,
            'work_id'      => $w1->id,
            'user_id'      => $this->user->id,
            // settings/options می‌توانند string(json) هم باشند
            'settings'    => json_encode([
                'fit_mode' => 'contain',
                'rotation' => 0,
            ]),
        ];


        // ایجاد (کلاینت)
        $res = $this->post('/api/client/products', $payload);
        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('user_id', $this->user->id)->first();
        $this->assertNotNull($product);

        // حذف (کلاینت)
        $del = $this->delete('/api/client/products/' . $product->id);
        $del->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
    }

    /** ----------------- Client: update ----------------- */
    public function test_client_updating_product(): void
    {
        $category = Category::factory()->create();
        $w1 = Work::create([
            'user_id' => $this->user->id,
            'title' => 'Alpha',
            'slug' => Work::makeSlug('Alpha'),
            'description' => 'desc A',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // محصول اولیه متعلق به همین کاربر
        $product = Product::create([
            'user_id'     => $this->user->id,
            'work_id'      => $w1->id,
            'category_id' => $category->id,
            'name'        => 'طرح قدیمی',
            'slug'        => 'old-slug-1234',
            'price'       => 150000,
            'currency'    => 'IRR',
            'type'        => 'standard',
            'status'      => 1,
        ]);




        $update = [
            'name'   => 'طرح جدید',
            'price'  => 199000,
            'status' => 1,
            'user_id'     => $this->user->id,
            'work_id'      => $w1->id,
            'category_id' => $category->id,
            // اگر slug خالی بفرستی، کنترلر دوباره می‌سازه
            'slug'   => '',
        ];

        $res = $this->put('/api/client/products/' . $product->id, $update);
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
        $w1 = Work::create([
            'user_id' => $this->user->id,
            'title' => 'Alpha',
            'slug' => Work::makeSlug('Alpha'),
            'description' => 'desc A',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Product::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'name'        => 'محصول ۱',
            'work_id'      => $w1->id,
            'slug'        => 'product-1-'.\Str::random(4),
            'price'       => 120000,
            'currency'    => 'IRR',
            'type'        => 'standard',
            'status'      => 1,
        ]);

        // لیست صفحه‌بندی‌شده (کلاینت)
        $res = $this->get('/api/client/products?per_page=2');
        $res->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $first = Product::where('user_id', $this->user->id)->first();

        // دریافت تکی با ?id=
        $res2 = $this->get('/api/client/products?id=' . $first->id);
        $res2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /** ----------------- Admin: CRUD + restore ----------------- */
    public function test_admin_crud_and_restore(): void
    {
        // نقش ادمین
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();
        $w1 = Work::create([
            'user_id' => $this->user->id,
            'title' => 'Alpha',
            'slug' => Work::makeSlug('Alpha'),
            'description' => 'desc A',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // ایجاد (ادمین)
        $payload = [
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'name'        => 'هودی مشکی',
            'price'       => 350000,
            'type'        => 'standard',
            'status'      => 1,
            'work_id'      => $w1->id,
        ];
        $create = $this->post('/api/products', $payload);
        $create->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $product = Product::where('name', 'هودی مشکی')->firstOrFail();

        // لیست (ادمین)
        $list = $this->get('/api/products?per_page=1');
        $list->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        // تکی با id (ادمین)
        $single = $this->get('/api/products?id=' . $product->id);
        $single->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );

        // بروزرسانی (ادمین)
        $update = $this->put('/api/products/' . $product->id, [
            'name'  => 'هودی سرمه‌ای',
            'price' => 360000,
            'work_id'      => $w1->id,
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
        ]);
        $update->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $product->id]),
        ]);
        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'هودی سرمه‌ای',
            'price' => 360000,
            'work_id'      => $w1->id,
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
        ]);


        // حذف (ادمین) - soft delete
        $delete = $this->delete('/api/products/' . $product->id);
        $delete->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // بازیابی (ادمین)
        $restore = $this->post('/api/products/' . $product->id . '/restore');
        $restore->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.restoredSuccessfully'),
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }
public function test_client_bulk_store_with_global_image_and_per_category_settings(): void
    {
        // داده‌های لازم: دو دسته + یک work متعلق به کاربر
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();
        $work = Work::factory()->create(['user_id' => $this->user->id]);

        $globalSettings = ['fit' => 'contain', 'offset' => ['x' => 0, 'y' => 0]];
        $cat1Settings   = ['fit' => 'cover',   'scale'  => 1.1];
        $cat2Settings   = ['fit' => 'contain', 'scale'  => 0.9];

        $payload = [
            'work_id'   => $work->id,
            'name'      => 'طرح مشترک',
            'price'     => 150000,
            'status'    => 1,
            'sku'       => 'TS-2025',
            'settings'  => json_encode($globalSettings),

            // تصویر عمومی برای همه‌ی دسته‌ها
            'image'     => $this->fakePng('common.png'),

            // هر دسته یک محصول؛ هرکدام settings جدا
            'categories' => [
                [
                    'category_id' => $c1->id,
                    'address'     => 'node:12',
                    'settings'    => json_encode($cat1Settings),
                    // mockup_id اگر داری می‌تونی اضافه کنی
                    // 'mockup_id' => Mockup::factory()->create()->id,
                ],
                [
                    'category_id' => $c2->id,
                    // این یکی قیمت اختصاصی هم override می‌کند
                    'price'       => 180000,
                    'settings'    => json_encode($cat2Settings),
                ],
            ],
        ];

        $res = $this->post('/api/client/products/bulk', $payload);
        $res->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => __('general.savedSuccessfully'),
                'data'    => ['work_id' => $work->id, 'count' => 2],
            ]);

        // بررسی دیتابیس: دقیقاً 2 محصول با work_id مشترک و categoryهای متفاوت
        $products = Product::where('user_id', $this->user->id)
            ->where('work_id', $work->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $products);

        $p1 = $products[0];
        $p2 = $products[1];

        // محصول اول
        $this->assertSame($work->id, $p1->work_id);
        $this->assertSame($c1->id,   $p1->category_id);
        $this->assertSame(150000,    (int) $p1->price);
        $this->assertSame(1,         (int) $p1->status);
        $this->assertNotEmpty($p1->original_path);
        $this->assertNotEmpty($p1->preview_path);
        $this->assertIsArray($p1->settings);
        $this->assertSame('cover',   $p1->settings['fit']);
        $this->assertSame(1.1,       (float) $p1->settings['scale']);

        // محصول دوم
        $this->assertSame($work->id, $p2->work_id);
        $this->assertSame($c2->id,   $p2->category_id);
        $this->assertSame(180000,    (int) $p2->price); // override شده
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
        $work = Work::factory()->create(['user_id' => $this->user->id]);

        // هیچ تصویر عمومی نیست، و فقط برای یکی از دسته‌ها تصویر می‌فرستیم
        $payload = [
            'work_id'   => $work->id,
            'name'      => 'بدون تصویر عمومی',
            'price'     => 1000,
            'categories' => [
                [
                    'category_id' => $c1->id,
                    'image' => $this->fakePng('c1.jpg'),
                ],
                [
                    'category_id' => $c2->id,
                    // تصویر ندارد → باید 422 بدهد
                ],
            ],
        ];

        $res = $this->post('/api/client/products/bulk', $payload);
        $res->assertStatus(422);
    }

    public function test_client_bulk_store_with_per_category_images_only(): void
    {
        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();
        $work = Work::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'work_id'   => $work->id,
            'name'      => 'تصاویر اختصاصی دسته‌ها',
            'price'     => 220000,
            'status'    => 1,
            'categories' => [
                [
                    'category_id' => $c1->id,
                    'image' => $this->fakePng('c1.png'),
                    'settings'    => json_encode(['fit' => 'cover']),
                ],
                [
                    'category_id' => $c2->id,
                    'image' => $this->fakePng('c2.png'),
                    'settings'    => json_encode(['fit' => 'contain']),
                ],
            ],
        ];

        $res = $this->post('/api/client/products/bulk', $payload);
        $res->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => __('general.savedSuccessfully'),
                'data'    => ['work_id' => $work->id, 'count' => 2],
            ]);

        $this->assertDatabaseCount('products', 2);

        $ps = Product::where('work_id', $work->id)->orderBy('id')->get();
        $this->assertSame($c1->id, $ps[0]->category_id);
        $this->assertSame('cover', $ps[0]->settings['fit']);

        $this->assertSame($c2->id, $ps[1]->category_id);
        $this->assertSame('contain', $ps[1]->settings['fit']);
    }
}
