<?php

namespace Tests\Feature;

use App\Models\Category;
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

class CategoryTest extends TestCase
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
        $this->role->givePermissionTo('category.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_category() : void
    {
        $this->user->assignRole($this->role);
        $image = UploadedFile::fake()->image('photo.png');

        $response = $this->post('/api/categories', ['name' => 'name1', 'data' => '{}', 'images' => [$image]]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $category = Category::where('name', 'name1')->first();
        $modelName = class_basename($category);
        $directory = 'public/models/' . $modelName . '/' . $modelName . $category->id;
        assertTrue(Storage::exists($directory));

        //deleting
        $response = $this->delete('/api/categories/' . $category->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $category->id]),
        ]);

        assertNotTrue(Storage::exists($directory));
    }

    /**
     * @return void
     */
    public function test_updating_category() : void
    {
        $this->user->assignRole($this->role);
        $image = UploadedFile::fake()->image('photo.png');
        $category = Category::factory()->create();

        $response = $this->patch('/api/categories/' . $category->id, ['name' => 'name2', 'data' => '{}', 'images' => [$image]]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $category->id]),
        ]);



        $modelName = class_basename($category);
        $directory = 'public/models/' . $modelName . '/' . $modelName . $category->id;
        assertTrue(Storage::exists($directory));
    }

    /**
     * @return void
     */
    public function test_fetching_categories() : void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $response = $this->get('/api/categories/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Category::first();

        $response2 = $this->get('/api/categories?id=' . $firstModel->id);

        $response2->assertStatus(200);
    }
}
