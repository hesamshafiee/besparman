<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use function PHPUnit\Framework\assertNotTrue;
use function PHPUnit\Framework\assertTrue;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $permission;
    private $role;

    /**
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('product.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_product() : void
    {
        $this->user->assignRole($this->role);
        $image = UploadedFile::fake()->image('photo.png');

        $response = $this->post('/api/products', ['name' => 'name1', 'description' => 'description', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test', 'images' => [$image]]);

        $response->assertStatus(200);

        $product = Product::where('name', 'name1')->first();
        $modelName = class_basename($product);
        $directory = 'public/models/' . $modelName . '/' . $modelName . $product->id;
        assertTrue(Storage::exists($directory));

        //deleting
        $response = $this->delete('/api/products/' . $product->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $product->id]),
        ]);

        assertNotTrue(Storage::exists($directory));
    }

    /**
     * @return void
     */
    public function test_updating_product() : void
    {
        $this->user->assignRole($this->role);
        $image = UploadedFile::fake()->image('photo.png');
        $product = Product::create(['name' => 'name', 'description' => 'description', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test']);

        $response = $this->patch('/api/products/' . $product->id, ['name' => 'name2', 'description' => 'description2', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'images' => [$image]]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $product->id]),
        ]);



        $modelName = class_basename($product);
        $directory = 'public/models/' . $modelName . '/' . $modelName . $product->id;
        assertTrue(Storage::exists($directory));
    }




    public function test_bulk_updating_products() : void
    {
        $this->user->assignRole($this->role);
        
        $product1 = Product::create(['name' => 'name1', 'description' => 'description1', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test1']);
        $product2 = Product::create(['name' => 'name2', 'description' => 'description2', 'price' => '300', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test2']);

        $response = $this->patch('/api/products/bulk-update', [
            'products' => [
                ['id' => $product1->id, 'name' => 'updated_name1', 'description' => 'updated_description1', 'price' => '250', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test1'],
                ['id' => $product2->id, 'name' => 'updated_name2', 'description' => 'updated_description2', 'price' => '350', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test2']
            ]
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully'),
        ]);

    }

    /**
     * @return void
     */
    public function test_fetching_products() : void
    {
        $this->user->assignRole($this->role);

        Product::create(['name' => 'name', 'description' => 'description', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test']);

        $response = $this->get('/api/products/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Product::first();

        $response2 = $this->get('/api/products?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_products() : void
    {
        Product::create(['name' => 'name', 'description' => 'description', 'price' => '200', 'type' => Product::TYPE_CART, 'sku' => 'test']);

        $response = $this->get('/api/clients/products');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Product::where('type', Product::TYPE_CART)->first();
        $firstModel->status = Product::STATUS_ACTIVE;
        $firstModel->save();

        $response2 = $this->get('/api/clients/products?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_assign_category_to_product(): void
    {
        $this->user->assignRole($this->role);
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $response = $this->post('/api/products/assign-category/' . $product->id, ['category_id' => $category->id, 'address' => '{}']);
        $response->assertStatus(200);
        assertTrue($product->categories()->exists());
    }

//    /**
//     * @return void
//     */
//    public function test_options() : void
//    {
//        $this->user->assignRole($this->role);
//
//        Product::create(['name' => 'name', 'description' => 'description', 'price' => '200', 'type' => Product::TYPE_CELL_INTERNET_PACKAGE, 'sku' => 'test']);
//        $product = Product::first();
//
//
//        $response = $this->post('/api/products/options/' . $product->id, ['option' => 'color', 'value' => 'green']);
//
//        $response->assertStatus(200)->assertJson([
//            'status' => true,
//            'message' => __('general.savedSuccessfully'),
//        ]);
//    }
}
