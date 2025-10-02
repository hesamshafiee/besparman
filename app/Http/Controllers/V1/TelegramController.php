<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserTelegramAccountResource;
use App\Models\User;
use App\Models\UserTelegramAccount;
use App\Notifications\SendTelegramMessage;
use App\Services\V1\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class TelegramController extends Controller
{

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Telegram
     */
    public function clientIndex(Request $request): JsonResponse
    {

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $user = Auth::user();

        $userTelegramAccount = UserTelegramAccount::where('user_id', $user->id)
            ->orderBy($order, $typeOrder)
            ->paginate($perPage);

        return response()->jsonMacro(UserTelegramAccountResource::collection($userTelegramAccount));
    }

    /**
     *
     * @param UserTelegramAccount $userTelegramAccount
     * @return JsonResponse
     * @group Telegram
     */
    public function clientDestroy(UserTelegramAccount $userTelegramAccount): JsonResponse
    {
        if ($userTelegramAccount->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'access denied.'
            ], 403);
        }

        if ($userTelegramAccount->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $userTelegramAccount->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @param string|null $code
     * @return JsonResponse
     * @group Telegram
     */
    public function activeLink(?string $code = null): JsonResponse
    {
        $id = Auth::id();
        $user = User::find($id);
        if (!$user) {

            return response()->json(['message' => 'کاربر یافت نشد'], 404);
        }


        $otp = new OtpService($user);
        if (is_null($code) && Auth::check()) {
            return $otp->serviceController();
        }


        $response = $otp->serviceController($code);

        if (!$response) {
            return response()->json([
                'status' => false,
                'message' => 'کد وارد شده معتبر نیست یا منقضی شده است.',
            ], 422);
        }

        if ($user->updated_at < now()->subMinutes(15) || !$user->telegram_verify_code) {
            $code = strtoupper(Str::random(8));
            $user->telegram_verify_code = $code;
            $user->save();
        }

        $code = $user->telegram_verify_code;
        $link = "https://t.me/esajReportsBot?start=" . $code;

        if (env('APP_ENV') == 'development') {
            $link = "https://t.me/esajtestbot?start=" . $code;
        }

        return response()->json([
            'message' => 'اتصال به تلگرام',
            'link' => $link
        ]);
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Telegram
     */
    public function saveTelegramId(Request $request)
    {
        $update = $request->all();
        if (!isset($update['message']['chat']['id'])) {
            return response()->json(['message' => 'ignored'], 400);
        }

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';

        if (strpos($text, '/start') === 0) {
            $parts = explode(' ', $text);
            $code = $parts[1] ?? null;

            if ($code) {
                $user = User::where('telegram_verify_code', $code)->first();

                if ($user) {
                    $from = $update['message']['from'];
                    $username = $from['username'] ?? null;
                    $firstName = $from['first_name'] ?? null;
                    $lastName = $from['last_name'] ?? null;
                    $name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

                    $userTelegram = $user->telegramAccounts()->where('telegram_id', $chatId)->first();
                    if (!$userTelegram) {
                        $user->telegramAccounts()->create([
                            'telegram_id' => $chatId,
                            'username'    => $username,
                            'name'        => $name,
                            'label' => 'Default'
                        ]);
                    }else {
                        $userTelegram->update([
                            'username' => $username,
                            'name'     => $name,
                        ]);
                }
                    $user->telegram_verify_code = null;
                    $user->save();
                    $telegram = new SendTelegramMessage();
                    $telegram->sendTelegramMessageForUsers("حساب تلگرام شما به سایت متصل شد.", $chatId);
                } else {
                    $telegram = new SendTelegramMessage();
                    $telegram->sendTelegramMessageForUsers("کد معتبر نیست.لینک جدید بسازید", $chatId);
                }
            } else {
                $telegram = new SendTelegramMessage();
                $telegram->sendTelegramMessageForUsers("سلام 👋 برای اتصال به سایت روی لینک داخل پروفایل کلیک کن.", $chatId);
            }
        }
    }
}
