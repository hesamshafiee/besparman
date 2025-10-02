<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\V1\Image\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImageTest extends TestCase
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
    public function test_create_get_list_delete_image_public() : void
    {
        // getting list of images
        $response = $this->list(Image::DRIVER_PUBLIC);
        $imageName = $response['imageName'];
        $array = $response['array'];
        $categoryId = $response['categoryId'];

        // fetching image
        $this->fetchImage($imageName, Image::DRIVER_PUBLIC);

        // deleting image
        $this->user->assignRole($this->role);
        $this->deleteImage($imageName, $array, $categoryId, Image::DRIVER_PUBLIC);


    }

    /**
     * @return void
     */
    public function test_create_get_list_delete_image_private() : void
    {
        $this->user->assignRole($this->role);
        // getting list of images
        $response = $this->list(Image::DRIVER_LOCAL);
        $imageName = $response['imageName'];
        $array = $response['array'];
        $categoryId = $response['categoryId'];

        // fetching image
        $this->fetchImage($imageName, Image::DRIVER_LOCAL);

        // deleting image
        $this->deleteImage($imageName, $array, $categoryId, Image::DRIVER_LOCAL);
    }

    /**
     * @return void
     */
    public function test_incorrect_path_list() : void
    {
        // getting list of images
        $response = $this->get('/api/images/list/private/category2/2');

        $response->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_incorrect_path_fetch() : void
    {
        // fetching image
        $response = $this->get('/api/images/public/get/test/78234');
        $response->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_incorrect_path_delete() : void
    {
        // deleting image
        $this->user->assignRole($this->role);
        $response = $this->delete('/api/images/delete/category/public/model');
        $response->assertStatus(404);
    }

    /**
     * @param string $imageName
     * @param array $array
     * @param int $categoryId
     * @param string $driver
     * @return void
     */
    private function deleteImage(string $imageName, array $array, int $categoryId, string $driver) : void
    {
        $response = $this->delete('/api/images/delete/' . $imageName . '/' . $driver . '/model');
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $imageName]),
        ]);

        if ($driver === Image::DRIVER_LOCAL) {
            $response = $this->get('/api/images/list/private/category/' . $categoryId);

        } else {
            $response = $this->get('/api/images/list/public/category/' . $categoryId);
        }

        $array2 = $response->json('paths');

        $this->assertTrue(count($array) > count($array2));
    }

    /**
     * @param string $imageName
     * @param string $driver
     * @return void
     */
    private function fetchImage(string $imageName, string $driver) : void
    {
        if ($driver === Image::DRIVER_LOCAL) {
            $response = $this->get('/api/images/get/' . $imageName . '/78234');
        } else {
            $response = $this->get('/api/images/public/get/' . $imageName . '/78234');
        }
        $response->assertStatus(200);
    }

    /**
     * @param string $driver
     * @param string $group
     * @return array
     */
    private function list(string $driver, string $group = 'category') : array
    {
        $image = UploadedFile::fake()->image('photo1.png');
        $category = Category::factory()->create();
        Image::modelImages($category, [$image], $driver);
        if ($driver === Image::DRIVER_LOCAL) {
            $response = $this->get('/api/images/list/private/' . $group . '/' . $category->id);

        } else {
            $response = $this->get('/api/images/list/public/' . $group . '/' . $category->id);
        }

        $array = $response->json('paths');
        $imageName = reset($array);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['paths'])
        );

        return ['imageName' => $imageName, 'array' => $array, 'categoryId' => $category->id];
    }
}
