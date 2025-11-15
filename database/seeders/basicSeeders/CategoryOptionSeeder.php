<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Category;
use App\Models\Option;
use Illuminate\Database\Seeder;

class CategoryOptionSeeder extends Seeder
{
    public function run(): void
    {
        $color    = Option::where('code', 'color')->first();
        $size     = Option::where('code', 'size')->first();
        $material = Option::where('code', 'material')->first();

        // هر دسته‌ای که وجود دارد، مورد استفاده قرار می‌گیرد

        $this->attachOptions('Clothing', [
            $color?->id    => ['is_required' => true,  'is_active' => 1, 'sort_order' => 0],
            $size?->id     => ['is_required' => true,  'is_active' => 1, 'sort_order' => 1],
            $material?->id => ['is_required' => false, 'is_active' => 1, 'sort_order' => 2],
        ]);

        $this->attachOptions('Shoes', [
            $color?->id => ['is_required' => true, 'is_active' => 1, 'sort_order' => 0],
            $size?->id  => ['is_required' => true, 'is_active' => 1, 'sort_order' => 1],
        ]);

        $this->attachOptions('Accessories', [
            $color?->id => ['is_required' => false, 'is_active' => 1, 'sort_order' => 0],
        ]);
    }

    private function attachOptions(string $categoryName, array $options): void
    {
        $category = Category::where('name', $categoryName)->first();

        if (!$category) {
            return; // اگر همچین کتگوری‌ای نداریم، بی‌سروصدا رد شو
        }

        foreach ($options as $optionId => $pivot) {
            // اگر Option پیدا نشده بود (null->id) تو آرایه null شده، اینجا ردش می‌کنیم
            if (!$optionId) {
                continue;
            }

            $category->options()->syncWithoutDetaching([
                $optionId => $pivot,
            ]);
        }
    }
}
