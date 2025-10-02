<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\UserTelegramAccount;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MainPageReportController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group MainPageReport
     */
    public function report(Request $request): JsonResponse
    {
        $this->authorize('update', Operator::class);

        $telegramUsageCount = Cache::remember('report:telegram_usage_count', 86400, function () {
            return UserTelegramAccount::distinct('user_id')->count('user_id');
        });

        $multipleTopUpTransactionCount = Cache::remember('report:multiple_top_up_transaction_count', 86400, function () {
            return WalletTransaction::whereNotNull('multiple_top_up_id')->count();
        });

        $multipleTopUpUsersCount = Cache::remember('report:multiple_top_up_users_count', 86400, function () {
            return WalletTransaction::whereNotNull('multiple_top_up_id')
                ->distinct('user_id')
                ->count('user_id');
        });

        return response()->json([
            'telegram_usage_count' => $telegramUsageCount,
            'multiple_top_up_transaction_count' => $multipleTopUpTransactionCount,
            'multiple_top_up_users_count' => $multipleTopUpUsersCount,
        ]);
    }
}
