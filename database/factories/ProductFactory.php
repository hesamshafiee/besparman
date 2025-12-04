<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [

            // روابط
            'user_id'    => User::factory(),
            'work_id'    => Work::factory(),
            'variant_id' => Variant::factory(),

            // عنوان‌ها
            'name'    => $this->faker->words(3, true),
            'slug'    => function (array $attributes) {
                return Str::slug($attributes['name']) . '-' . Str::random(5);
            },
            'name_en' => $this->faker->words(3, true),

            // توضیحات
            'description'       => $this->faker->sentence(),
            'description_full'  => $this->faker->paragraph(4),

            // قیمت / کد
            'sku'      => strtoupper(Str::random(10)),
            'price'    => $this->faker->numberBetween(10000, 500000),
            'currency' => 'IRR',
            'type'     => 'standard',

            // تنظیمات فروش / نمایش
            'minimum_sale' => $this->faker->numberBetween(1, 10),
            'dimension'    => $this->faker->randomElement(['10x10', '20x30', '50x70']),
            'score'        => $this->faker->numberBetween(0, 5),
            'status'       => $this->faker->randomElement([0, 1]),

            // سورت
            'sort' => $this->faker->numberBetween(1, 999),

            // فایل‌ها
            'original_path' => null,
            'preview_path'  => null,

            // JSON ها (فرض بر اینه توی مدل cast شدن به array/json)
            'settings' => [
                'color' => $this->faker->safeColorName(),
                'size'  => $this->faker->randomElement(['S', 'M', 'L']),
            ],
            'options' => [
                'material' => $this->faker->randomElement(['paper', 'canvas', 'vinyl']),
            ],
            'meta' => [
                'tags' => $this->faker->words(3),
            ],
        ];
    }
}
