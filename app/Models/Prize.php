<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

class Prize extends Model
{
    use HasFactory, SortableTrait, HasTags;
    use LogsActivityWithRequest;


    const TYPE_CELL_INTERNET_PACKAGE = 'cell_internet';
    const TYPE_TD_LTE_INTERNET_PACKAGE = 'td_lte_internet';
    const TYPE_CELL_DIRECT_CHARGE = 'cell_direct_charge';
    const TYPE_CELL_INTERNET_DIRECT_CHARGE = 'cell_internet_direct_charge';
    const TYPE_AMAZING_CELL_DIRECT_CHARGE = 'cell_amazing_direct_charge';
    const TYPE_INCREASE_PRIZE= 'increase_prize';
    const TYPE_PHYSICAL = 'physical';
    const TYPE_DISCOUNT = 'discount';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        'name',
        'price',
        'point',
        'type',
        'operator_id',
        'profile_id',
        'ext_id',
        'operator_type',
        'description',
        'url'
        ];

    /**
     * @return BelongsTo
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * @return HasMany
     */
    public function prizeItems(): HasMany
    {
        return $this->hasMany(PrizeItem::class);
    }

    /**
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
