<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeItem extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const USED_TRUE = 1;
    const USED_FALSE = 0;


    protected $fillable = [
        'code',
        'prize_id'
        ];

    /**
     * @return BelongsTo
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }
}
