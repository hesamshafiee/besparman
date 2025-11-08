<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Product extends Model implements Sortable
{
    use HasFactory, SortableTrait;
    use LogsActivityWithRequest;



    const TYPE_CELL_INTERNET_PACKAGE = 'cell_internet';
    const TYPE_TD_LTE_INTERNET_PACKAGE = 'td_lte_internet';
    const TYPE_CELL_DIRECT_CHARGE = 'cell_direct_charge';
    const TYPE_CELL_AMAZING_DIRECT_CHARGE = 'cell_amazing_direct_charge';
    const TYPE_CELL_INTERNET_DIRECT_CHARGE = 'cell_internet_direct_charge';
    const TYPE_CARD_CHARGE = 'card_charge';
    const TYPE_PHYSICAL_CARD_CHARGE = 'physical_card_charge';

    const SIM_CARD_TYPE_CREDIT = 'credit';
    const SIM_CARD_TYPE_PERMANENT = 'permanent';

    const TYPE_CART = 'cart';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * @return MorphToMany
     */
    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    /**
     * @return MorphToMany
     */
    public function sales(): MorphToMany
    {
        return $this->morphToMany(Sale::class, 'saleable');
    }

    /**
     * @return MorphToMany
     */
    // public function categories(): MorphToMany
    // {
    //     return $this->morphToMany(Category::class, 'categorizable')->withPivot('address');
    // }

    /**
     * @return HasOne
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class);
    }

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'category_name',
        'original_path',
        'preview_path',
        'price',
        'second_price',
        'status',
        'operator_id',
        'order',
        'period',
        'profile_id',
        'sim_card_type',
        'type',
        'settings',
    ];

    protected $casts = [
        'options' => 'array',
        'price'        => 'integer',
        'second_price' => 'integer',
        'status'       => 'integer',
        'settings'     => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function mockupRenders()
    {
        return $this->hasMany(ProductMockupRender::class);
    }
}
