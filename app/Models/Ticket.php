<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Ticket extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    const STATUS_ANSWERING = 'answering';
    const STATUS_ANSWERED = 'answered';
    const STATUS_CLOSED = 'closed';



    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_BILLING = 'billing';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_FEATURE_REQUEST = 'feature_request';
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_FEEDBACK = 'feedback';



        /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return  $this->belongsTo(User::class);
    }



     public static function categories()
    {
        return [
            self::CATEGORY_TECHNICAL,
            self::CATEGORY_BILLING,
            self::CATEGORY_ACCOUNT,
            self::CATEGORY_FEATURE_REQUEST,
            self::CATEGORY_GENERAL,
            self::CATEGORY_FEEDBACK,
        ];
    }
}
