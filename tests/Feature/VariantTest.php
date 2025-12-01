<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Variant;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VariantTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $clientUser;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed'); // سیدرها اجرا می‌شن ولی تست خودش هم آماده‌سازی لازم رو انجام می‌ده

        // ادمین
        $this->adminUser = User::factory()->create([
            'type'            => 'panel',
            'profile_confirm' => now(),
        ]);

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('variant.*');
        $this->adminUser->assignRole($this->role);

        // کلاینت
        $this->clientUser = User::factory()->create([
            'type'            => 'panel',
            'profile_confirm' => now(),
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);
    }

    /**
     * یه کتگوری واقعی از Seeder برمی‌داریم (مثلا یقه‌گرد اگر وجود داشته باشد)
     */
    protected function getCategoryForVariant(): Category
    {
        return Category::where('name', 'یقه گرد')->first()
            ?? Category::where('show_in_work', 1)->first()
            ?? Category::firstOrFail();
    }

    /**
     * اگر options/value های مورد انتظار در DB نیستند، این متد آنها را موقت می‌سازد
     * تا تست وابستگی‌محور نباشد. (بدون تغییر در سیدرهای پروژه)
     */
    protected function ensureOptionsAndValuesExist(): void
    {
        // اگر گزینه color وجود نداره، بسازش و چند value اضافه کن
        $color = Option::firstOrCreate(
            ['code' => 'color'],
            [
                'name'         => 'رنگ',
                'type'         => 'color',
                'display_type' => 'color-picker',
                'is_required'  => true,
                'is_active'    => 1,
                'meta'         => [],
                'sort_order'   => 1,
            ]
        );

        $size = Option::firstOrCreate(
            ['code' => 'size'],
            [
                'name'         => 'سایز',
                'type'         => 'select',
                'display_type' => 'select',
                'is_required'  => true,
                'is_active'    => 1,
                'meta'         => [],
                'sort_order'   => 2,
            ]
        );

        // یک‌بار برای رنگ‌ها
        foreach ([
            ['code' => 'red',   'name' => 'قرمز',  'meta' => ['color' => '#FF0000']],
            ['code' => 'blue',  'name' => 'آبی',   'meta' => ['color' => '#0000FF']],
            ['code' => 'white', 'name' => 'سفید',  'meta' => ['color' => '#FFFFFF']],
        ] as $v) {
            OptionValue::firstOrCreate(
                ['option_id' => $color->id, 'code' => $v['code']],
                ['name' => $v['name'], 'meta' => $v['meta'] ?? [], 'is_active' => 1, 'sort_order' => 0]
            );
        }

        // یک‌بار برای سایزها
        foreach ([
            ['code' => 's',  'name' => 'S'],
            ['code' => 'm',  'name' => 'M'],
            ['code' => 'l',  'name' => 'L'],
        ] as $v) {
            OptionValue::firstOrCreate(
                ['option_id' => $size->id, 'code' => $v['code']],
                ['name' => $v['name'], 'meta' => [], 'is_active' => 1, 'sort_order' => 0]
            );
        }

        // توجه: اگر پروژه‌ات گزینه/مقادیر دیگری هم از قبل داشت، آنها دست نخورده باقی می‌مانند
    }

    /**
     * از options/option_values موجود (از Seeder یا ensure) چند تا می‌گیریم
     */
    protected function getSomeOptionValueIds(): array
    {
        // اگر سِیدرها مقادیر رو نساخته باشند، این helper اون‌ها رو ایجاد می‌کند
        $this->ensureOptionsAndValuesExist();

        $color = Option::where('code', 'color')->first();
        $size  = Option::where('code', 'size')->first();

        $ids = [];

        if ($color) {
            $val = OptionValue::where('option_id', $color->id)->value('id');
            if ($val) $ids[] = $val;
        }
        if ($size) {
            $val = OptionValue::where('option_id', $size->id)->value('id');
            if ($val) $ids[] = $val;
        }

        // فیلتر nullها
        return array_values(array_filter($ids));
    }

    public function test_admin_can_list_variants_with_pagination_and_sort(): void
    {
        $category = $this->getCategoryForVariant();

        Variant::create([
            'category_id' => $category->id,
            'sku'         => 'SKU-1',
            'stock'       => 10,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);

        Variant::create([
            'category_id' => $category->id,
            'sku'         => 'SKU-2',
            'stock'       => 5,
            'add_price'   => 500,
            'is_active'   => true,
        ]);

        $res = $this->getJson('/api/variants?order=id&type_order=asc&per_page=10');

        $res->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );
    }

    public function test_admin_can_fetch_single_variant_by_id(): void
    {
        $category = $this->getCategoryForVariant();

        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'SKU-SINGLE',
            'stock'       => 3,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $res = $this->getJson('/api/variants?id=' . $variant->id);

        $res->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.id', $variant->id)->etc()
        );
    }

    public function test_admin_can_store_variant_with_option_values_and_auto_sku(): void
    {
        $category = $this->getCategoryForVariant();
        $optionValueIds = $this->getSomeOptionValueIds();

        $payload = [
            'category_id'      => $category->id,
            'stock'            => 7,
            'add_price'        => 25000,
            'is_active'        => true,
            'option_value_ids' => $optionValueIds,
        ];

        $res = $this->postJson('/api/variants', $payload);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $this->assertDatabaseHas('variants', [
            'category_id' => $category->id,
            'stock'       => 7,
            'add_price'   => 25000,
        ]);


        $variant = Variant::latest('id')->first();
        $this->assertNotNull($variant);
        $this->assertNotNull($variant->sku);

        // بررسی اتصال pivot (ممکن است فقط یک id در array باشد)
        $this->assertDatabaseHas('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $optionValueIds[0] ?? null,
        ]);
    }

    public function test_admin_can_update_variant_and_sync_option_values_and_sku(): void
    {
        $category = $this->getCategoryForVariant();

        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'OLD-SKU',
            'stock'       => 2,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);

        $optionValueIds = $this->getSomeOptionValueIds();

        $payload = [
            'stock'            => 9,
            'add_price'        => 7500,
            'is_active'        => false,
            'option_value_ids' => $optionValueIds,
        ];

        $res = $this->patchJson('/api/variants/' . $variant->id, $payload);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully'),
        ]);

        $variant->refresh();

        $this->assertEquals(9, $variant->stock);
        $this->assertEquals(7500, $variant->add_price);
        $this->assertFalse($variant->is_active);
        $this->assertNotEquals('OLD-SKU', $variant->sku); // چون دوباره با option_value ها ساخته شده

        $this->assertDatabaseHas('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $optionValueIds[0] ?? null,
        ]);
    }

    public function test_admin_can_delete_variant(): void
    {
        $category = $this->getCategoryForVariant();

        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'DEL-SKU',
            'stock'       => 3,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $res = $this->deleteJson('/api/variants/' . $variant->id);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $variant->id]),
        ]);

        $this->assertDatabaseMissing('variants', ['id' => $variant->id]);
    }

    public function test_client_index_list_and_show_by_id(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $category = $this->getCategoryForVariant();

        $v1 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C1',
            'stock'       => 1,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);
        $v2 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C2',
            'stock'       => 2,
            'add_price'   => 2000,
            'is_active'   => true,
        ]);

        $list = $this->getJson('/api/clients/variants?order=id&type_order=asc&per_page=10');
        $list->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $show = $this->getJson('/api/clients/variants?id=' . $v1->id);
        $show->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.id', $v1->id)->etc()
        );
    }

    public function test_client_cannot_store_variant(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $category = $this->getCategoryForVariant();
        $optionValueIds = $this->getSomeOptionValueIds();

        $payload = [
            'category_id'      => $category->id,
            'stock'            => 1,
            'add_price'        => 5000,
            'is_active'        => true,
            'option_value_ids' => $optionValueIds,
        ];

        $res = $this->postJson('/api/variants', $payload);

        // فرض اینه که policy یا middleware جلوی این کار رو می‌گیره (403)
        $res->assertStatus(403);
    }
}
