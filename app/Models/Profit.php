<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profit extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const TYPE_CELL_INTERNET_PACKAGE = 'cell_internet';
    const TYPE_TD_LTE_INTERNET_PACKAGE = 'td_lte_internet';
    const TYPE_CELL_DIRECT_CHARGE = 'cell_direct_charge';
    const TYPE_CELL_CARD_CHARGE = 'cell_card_charge';
    const TYPE_CELL_INTERNET_DIRECT_CHARGE = 'cell_internet_direct_charge';
    const TYPE_AMAZING_CELL_DIRECT_CHARGE = 'cell_amazing_direct_charge';
    const TYPE_CARD_CHARGE = 'card_charge';

    protected $fillable = [
        'operator_id',
        'type',
        'title',
        'profit',
        'status'
        ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }


}
