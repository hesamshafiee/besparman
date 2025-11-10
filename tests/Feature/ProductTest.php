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

        // کاربر لاگین
        $this->user = User::factory()->create();

        // نقش ادمین تستی
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('product.*'); // فرض: permission در پروژه‌ات ساخته می‌شود

        // احراز هویت
        Sanctum::actingAs($this->user, ['*']);
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
}
