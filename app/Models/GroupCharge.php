<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupCharge extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    const STATUS_CANCELED = 2;
    const STATUS_FINISHED = 1;

    const STATUS_PENDING = 0;

    const CHARGE_STATUS_CANCELED = 'canceled';
    const CHARGE_STATUS_DONE = 'done';

    const CHARGE_STATUS_PENDING = 'pending';
    const CHARGE_STATUS_DOING = 'doing';

    const TYPE_TOPUP = 'topup' ;
    const TYPE_TOPUP_PACKAGE = 'package' ;


    const CHARGE_FORCE_ACTIVE = 1;
    const CHARGE_FORCE_DEACTIVE = 0;

    const TIMESECONDALLOWEDFORCANCELLATION = 60;

    protected $fillable = [ 'phone_numbers', 'charge_status', 'phone_numbers_unsuccessful', 'phone_numbers_successful'];

    protected $casts = [
        'phone_numbers_successful' => 'array',
        'phone_numbers_unsuccessful' => 'array',
        'phone_numbers' => 'array',
    ];

    public function getPhoneNumbersAttribute($value){
        return json_decode($value) ;
    }

    public function getTopupInformationAttribute($value){
        return json_decode($value) ;
    }


    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return  $this->belongsTo(User::class);
    }




}
