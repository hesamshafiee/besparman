<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivityWithRequest;

class Product extends Model implements Sortable
{
    use SoftDeletes;
    use HasFactory, SortableTrait;
    use LogsActivityWithRequest;

    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        'user_id',
        'variant_id',      // 👈 به جای category_id
        'work_id',
        'name',
        'slug',
        'name_en',
        'description',
        'description_full',
        'sku',
        'price',
        'currency',
        'type',
        'minimum_sale',
        'dimension',
        'score',
        'status',
        'sort',
        'original_path',
        'preview_path',
        'settings',
        'options',
        'meta',
    ];

    protected $casts = [
        'price'    => 'integer',
        'status'   => 'integer',
        'score'    => 'integer',
        'sort'     => 'integer',
        'settings' => 'array',
        'options'  => 'array',
        'meta'     => 'array',
    ];
    public $sortable = [
        'order_column_name'  => 'sort',   // به‌جای "order"
        'sort_when_creating' => true,     // موقع ایجاد، آخر صف بذار
    ];

    /** روابط **/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // قبلاً category بود، الان محصول مستقیم به Variant وصل است
    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function mockupRenders()
    {
        return $this->hasMany(ProductMockupRender::class);
    }
}
