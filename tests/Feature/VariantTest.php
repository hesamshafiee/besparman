<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Variant;
use App\Models\Category;
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

    private $adminUser;
    private $clientUser;
    private $role;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed');

        // ادمین
        $this->adminUser = User::factory()->create([
            'type' => 'panel',
            'profile_confirm' => now(),
        ]);

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('variant.*');
        $this->adminUser->assignRole($this->role);

        // کلاینت
        $this->clientUser = User::factory()->create([
            'type' => 'panel',
            'profile_confirm' => now(),
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);
    }

    public function test_admin_can_list_variants_with_pagination_and_sort(): void
    {
        $category = Category::create([
            'name' => 'Category 1', // در صورت نیاز، فیلدهای دیگه‌ی دسته‌بندی‌ات رو هم اضافه کن
        ]);

        $v1 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C' . $category->id . '-A',
            'stock'       => 5,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);

        $v2 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C' . $category->id . '-B',
            'stock'       => 3,
            'add_price'   => 2000,
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/variants?order=id&type_order=asc&per_page=10');

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );
    }

    public function test_admin_can_fetch_single_variant_by_id(): void
    {
        $category = Category::create([
            'name' => 'Category Single',
        ]);

        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C' . $category->id . '-SINGLE',
            'stock'       => 3,
            'add_price'   => 2500,
            'is_active'   => true,
        ]);

        $res = $this->getJson('/api/variants?id=' . (int) $variant->id);

        $res->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.id', $variant->id)->etc()
        );
    }

    public function test_admin_can_store_variant_with_option_values_and_auto_sku(): void
    {
        $category = Category::create([
            'name' => 'T-Shirt',
        ]);

        // ساخت Optionها و OptionValueها مطابق مایگریشن تو
        $colorOption = Option::create([
            'name'         => 'Color',
            'code'         => 'color',
            'type'         => 'select',
            'display_type' => 'color-picker',
            'is_required'  => true,
            'is_active'    => true,
            'meta'         => null,
            'sort_order'   => 1,
        ]);

        $red = OptionValue::create([
            'option_id' => $colorOption->id,
            'name'      => 'Red',
            'code'      => 'red',
            'meta'      => json_encode(['color' => '#FF0000']),
            'is_active' => true,
            'sort_order'=> 1,
        ]);

        $sizeOption = Option::create([
            'name'         => 'Size',
            'code'         => 'size',
            'type'         => 'select',
            'display_type' => null,
            'is_required'  => true,
            'is_active'    => true,
            'meta'         => null,
            'sort_order'   => 2,
        ]);

        $medium = OptionValue::create([
            'option_id' => $sizeOption->id,
            'name'      => 'M',
            'code'      => 'm',
            'meta'      => null,
            'is_active' => true,
            'sort_order'=> 1,
        ]);

        $payload = [
            'category_id'      => $category->id,
            'stock'            => 10,
            'add_price'        => 15000,
            'is_active'        => true,
            'option_value_ids' => [$red->id, $medium->id],
        ];

        $res = $this->postJson('/api/variants', $payload);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $this->assertDatabaseHas('variants', [
            'category_id' => $category->id,
            'stock'       => 10,
            'add_price'   => 15000,
        ]);

        $variant = Variant::where('category_id', $category->id)->firstOrFail();

        // SKU باید ساخته شده باشد و شامل C{category_id} باشد
        $this->assertNotNull($variant->sku);
        $this->assertStringContainsString('C' . $category->id, $variant->sku);

        // جدول pivot باید رکوردها را داشته باشد
        $this->assertDatabaseHas('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $red->id,
        ]);
        $this->assertDatabaseHas('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $medium->id,
        ]);
    }

    public function test_admin_can_update_variant_and_sync_option_values_and_sku(): void
    {
        $category = Category::create([
            'name' => 'Category Update',
        ]);

        $colorOption = Option::create([
            'name'         => 'Color',
            'code'         => 'color',
            'type'         => 'select',
            'display_type' => null,
            'is_required'  => false,
            'is_active'    => true,
            'meta'         => null,
            'sort_order'   => 1,
        ]);

        $black = OptionValue::create([
            'option_id' => $colorOption->id,
            'name'      => 'Black',
            'code'      => 'black',
            'meta'      => null,
            'is_active' => true,
            'sort_order'=> 1,
        ]);

        $white = OptionValue::create([
            'option_id' => $colorOption->id,
            'name'      => 'White',
            'code'      => 'white',
            'meta'      => null,
            'is_active' => true,
            'sort_order'=> 2,
        ]);

        // ساخت واریانت اولیه با یک option_value
        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TEMP-SKU',
            'stock'       => 5,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);
        $variant->optionValues()->sync([$black->id]);

        $oldSku = $variant->sku;

        $payload = [
            'stock'            => 7,
            'add_price'        => 2000,
            'option_value_ids' => [$white->id],
        ];

        $res = $this->patchJson('/api/variants/' . $variant->id, $payload);

        $res->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully'),
        ]);

        $variant->refresh();

        $this->assertEquals(7, $variant->stock);
        $this->assertEquals(2000, $variant->add_price);

        // SKU باید عوض شده باشد
        $this->assertNotEquals($oldSku, $variant->sku);

        // فقط white در pivot باشد
        $this->assertDatabaseHas('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $white->id,
        ]);
        $this->assertDatabaseMissing('variant_option_value', [
            'variant_id'      => $variant->id,
            'option_value_id' => $black->id,
        ]);
    }

    public function test_admin_can_delete_variant(): void
    {
        $category = Category::create([
            'name' => 'Category Delete',
        ]);

        $variant = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'TO-DELETE',
            'stock'       => 1,
            'add_price'   => 0,
            'is_active'   => true,
        ]);

        $del = $this->deleteJson('/api/variants/' . $variant->id);

        $del->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $variant->id]),
        ]);

        // اگر Variant از SoftDeletes استفاده نمی‌کند:
        $this->assertDatabaseMissing('variants', ['id' => $variant->id]);

        // اگر SoftDeletes اضافه کردی، این رو جایگزین کن:
        // $this->assertSoftDeleted('variants', ['id' => $variant->id]);
    }

    public function test_client_index_list_and_show_by_id(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $category = Category::create([
            'name' => 'Category Client',
        ]);

        $v1 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C' . $category->id . '-V1',
            'stock'       => 3,
            'add_price'   => 1000,
            'is_active'   => true,
        ]);

        $v2 = Variant::create([
            'category_id' => $category->id,
            'sku'         => 'C' . $category->id . '-V2',
            'stock'       => 4,
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

        $category = Category::create([
            'name' => 'Category NoCreate',
        ]);

        $option = Option::create([
            'name'         => 'Color',
            'code'         => 'color',
            'type'         => 'select',
            'display_type' => null,
            'is_required'  => false,
            'is_active'    => true,
            'meta'         => null,
            'sort_order'   => 1,
        ]);

        $value = OptionValue::create([
            'option_id' => $option->id,
            'name'      => 'Blue',
            'code'      => 'blue',
            'meta'      => null,
            'is_active' => true,
            'sort_order'=> 1,
        ]);

        $payload = [
            'category_id'      => $category->id,
            'stock'            => 5,
            'add_price'        => 5000,
            'is_active'        => true,
            'option_value_ids' => [$value->id],
        ];

        $res = $this->postJson('/api/variants', $payload);

        // به خاطر authorize('create', Variant::class) باید 403 بده
        $res->assertStatus(403);
    }
}
