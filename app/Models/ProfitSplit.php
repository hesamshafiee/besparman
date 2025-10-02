<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitSplit extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = [
        'profit_id',
        'title',
        'presenter_profit',
        'seller_profit'
        ];

    /**
     * @return BelongsTo
     */
    public function profit(): BelongsTo
    {
        return $this->belongsTo(Profit::class);
    }

}
