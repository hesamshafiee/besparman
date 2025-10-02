<?php

namespace App\Models;

use App\Services\V1\Esaj\Aptel;
use App\Services\V1\Esaj\Irancell;
use App\Services\V1\Esaj\Mci;
use App\Services\V1\Esaj\Rightel;
use App\Services\V1\Esaj\Shatel;
use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    const MCI = 'mci';
    const IRANCELL = 'irancell';
    const APTEL = 'aptel';
    const SHATEL = 'shatel';
    const RIGHTEL = 'rightel';

    const TYPE_CREDIT_CELL_INTERNET_PACKAGE = 'credit_cell_internet';
    const TYPE_CREDIT_TD_LTE_INTERNET_PACKAGE = 'credit_td_lte_internet';
    const TYPE_CREDIT_CELL_AMAZING_DIRECT_CHARGE = 'credit_cell_amazing_direct_charge';
    const TYPE_CREDIT_CELL_DIRECT_CHARGE = 'credit_cell_direct_charge';
    const TYPE_CREDIT_CELL_INTERNET_DIRECT_CHARGE = 'credit_cell_internet_direct_charge';
    const TYPE_PERMANENT_CELL_INTERNET_PACKAGE = 'permanent_cell_internet';
    const TYPE_PERMANENT_TD_LTE_INTERNET_PACKAGE = 'permanent_td_lte_internet';
    const TYPE_PERMANENT_CELL_DIRECT_CHARGE = 'permanent_cell_direct_charge';
    const TYPE_PERMANENT_CELL_INTERNET_DIRECT_CHARGE = 'permanent_cell_internet_direct_charge';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = ['title', 'status', 'setting'];

    protected $casts = [
        'setting' => AsArrayObject::class,
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public static function getRemaining(): array
    {
        $IrancellRes = (new Irancell())->getRemaining();
        $RightelRes = (new Rightel())->getRemaining();
        $MciRes = (new Mci())->getRemaining();
        $AptelRes = (new Aptel())->getRemaining();
        $ShatelRes = (new Shatel())->getRemaining();

        return [
            'irancell' => $IrancellRes['irancell'] ?? null,
            'rightel' => $RightelRes['rightel'] ?? null,
            'mciPackagesRadin' => $MciRes['MCIPackagesRadin'] ?? null,
            'mciChargeRadin' => $MciRes['MCIChargeRadin'] ?? null,
            'mciIgap' => $MciRes['MCIIgap'] ?? null,
            'aptelccwallet' => $AptelRes['Aptelccwallet'] ?? null,
            'aptelcpwallet' => $AptelRes['Aptelcpwallet'] ?? null,
            'shatel' => $ShatelRes['Shatel'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public static function getOperatorType(Product $product): ?string
    {
        $operatorMap = [
            1 => 'mci',
            2 => 'irancell',
            3 => 'rightel',
            4 => 'aptel',
            5 => 'shatel',
        ];

        $operatorTypes = [
            'irancell' => [
                'permanent' => [
                    'cell_internet' => 5,
                    'cell_direct_charge' => 4,
                    'cell_amazing_direct_charge' => 0,
                ],
                'credit' => [
                    'cell_internet' => 5,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 2,
                ],
            ],
            'shatel' => [
                'credit' => [
                    'cell_internet' => 2,
                    'cell_direct_charge' => 1,
                    'cell_amazing_direct_charge' => 0,
                ],
                'permanent' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ]
            ],
            'rightel' => [
                'credit' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 1,
                    'cell_amazing_direct_charge' => 2,
                ],
                'permanent' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ]
            ],
            'mci' => [
                'credit' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ],
                'permanent' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ],
            ],
            'aptel' => [
                'credit' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ],
                'permanent' => [
                    'cell_internet' => 0,
                    'cell_direct_charge' => 0,
                    'cell_amazing_direct_charge' => 0,
                ],
            ],
        ];

        $operatorName = $operatorMap[$product->operator_id] ?? null;
        if (!$operatorName || !isset($operatorTypes[$operatorName])) {
            return '0';
        }

        $simCardType = $product->sim_card_type ?? null;
        if (!$simCardType || !isset($operatorTypes[$operatorName][$simCardType])) {
            return  '0';
        }

        $productType = $product->type ?? null;
        if (!$productType || !isset($operatorTypes[$operatorName][$simCardType][$productType])) {
            return  '0';
        }

        return (string) $operatorTypes[$operatorName][$simCardType][$productType];
    }
}
