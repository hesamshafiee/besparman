<?php

namespace Database\Factories;

use App\Models\Variant;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Variant>
 */
class VariantFactory extends Factory
{
    protected $model = Variant::class;

    public function definition(): array
    {
        return [

            // ارتباط با دسته‌بندی
            'category_id' => Category::factory(),

            // کد یکتا برای واریانت
            'sku' => strtoupper('VAR-' . Str::random(8)),

            // موجودی
            'stock' => $this->faker->numberBetween(0, 500),

            // قیمت افزوده (decimal)
            'add_price' => $this->faker->randomFloat(2, 0, 500),

            // وضعیت فعال بودن
            'is_active' => $this->faker->boolean(90),

            // تاریخ‌ها
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
