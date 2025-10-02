<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;

class Point extends Model
{
    use HasFactory, SortableTrait;
    use LogsActivityWithRequest;

    const TYPE_CELL_INTERNET_PACKAGE = 'cell_internet';
    const TYPE_TD_LTE_INTERNET_PACKAGE = 'td_lte_internet';
    const TYPE_CELL_DIRECT_CHARGE = 'cell_direct_charge';
    const TYPE_AMAZING_CELL_DIRECT_CHARGE = 'cell_amazing_direct_charge';
    const TYPE_CELL_INTERNET_DIRECT_CHARGE = 'cell_internet_direct_charge';

    protected $fillable = [
        'value',
        'point',
        'type',
        'status',
        'operator_id'
        ];
}
