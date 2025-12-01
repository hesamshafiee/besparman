<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Mockup;
use App\Models\ProductMockupRender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RenderMockupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $productId,
        public int $mockupId,
        public string $designPath,
        public array $settings = [] // override از کلاینت
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);
        $mockup  = Mockup::find($this->mockupId);
        if (!$product || !$mockup) return;

        $outDir  = 'uploads/mockups/'.date('Y/m/d').'/'.$product->id;
        $outName = $mockup->slug.'-'.uniqid().'.png';
        $outPath = $outDir.'/'.$outName;

        // رندر
        app(\App\Services\V1\Mockup\Renderer::class)
            ->render(mockupId: $mockup->id, designPath: $this->designPath, outputPath: $outPath, overrides: $this->settings);

        // ثبت خروجی
        ProductMockupRender::updateOrCreate(
            ['product_id' => $product->id, 'mockup_id' => $mockup->id],
            ['path' => $outPath, 'meta' => ['mockup_slug' => $mockup->slug, 'from_settings' => $this->settings]]
        );

        // اگر هنوز preview_path محصول خالیه، یکی رو ست کن
        if (!$product->preview_path) {
            $product->update(['preview_path' => $outPath]);
        }
    }
}
