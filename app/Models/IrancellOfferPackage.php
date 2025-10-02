<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrancellOfferPackage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'irancell_offer_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'mobile_number',
        'offerCode',
        'name',
        'amount',
        'offerType',
        'validityDays',
        'offerDesc',
    ];
}
