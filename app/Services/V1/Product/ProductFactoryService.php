<?php

namespace App\Services\V1\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMockupRender;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductFactoryService
{
    /**
     * هر فایل → یک محصول
     * settings روی خود محصول ذخیره می‌شود (json) و برای رندر هم استفاده می‌گردد.
     */
    public function createFromImages(array $images, int $categoryId, array $baseData, ?string $address = null): array
    {
        $createdProductIds = [];

        DB::transaction(function () use ($images, $categoryId, $baseData, $address, &$createdProductIds) {

            /** @var \App\Models\Category $category */
            $category = Category::with('mockups')->findOrFail($categoryId);

            foreach ($images as $file) {
                /** @var UploadedFile $file */

                // 1) ذخیره محصول
                $product = new Product();
                $product->user_id      = auth()->id();
                $product->category_id  = $category->id;
                $product->name         = $baseData['name']   ?? $this->defaultName($file, $category->name);
                $product->slug         = Str::slug($product->name).'-'.Str::random(6);
                $product->price        = $baseData['price']  ?? 0;
                $product->status       = $baseData['status'] ?? 1;
                $product->settings     = $baseData['settings'] ?? [];
                $product->save();

                // 2) مسیر فایل اصلی
                $storedPath = $file->store('uploads/designs/'.date('Y/m/d'), 'public');
                $product->update(['original_path' => $storedPath]);

                // 3) (اختیاری) سازگاری با pivot قدیمی
                if (method_exists($product, 'categories')) {
                    $product->categories()->attach($category->id, ['address' => $address]);
                }

                // 4) صف رندر برای هر موکاپ این دسته
                foreach ($category->mockups as $mockup) {
                    dispatch(new \App\Jobs\RenderMockupJob(
                        productId:  $product->id,
                        mockupId:   $mockup->id,
                        designPath: $storedPath,
                        settings:   $product->settings // override
                    ))->onQueue('render');
                }

                $createdProductIds[] = $product->id;
            }
        });

        return $createdProductIds;
    }

    protected function defaultName(UploadedFile $file, string $categoryName): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        return trim($categoryName.' - '.$base);
    }
}
