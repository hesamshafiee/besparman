<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Notifications\SendTelegramMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Sentry\Logs\Log;

class AlertController extends Controller
{
    /**
     *
     * @param Request $request
     * @return void
     * @group Alert
     */
    public function telegram(Request $request)
    {
        $message = $this->formatGrafanaMessage($request->all());

        $telegram = new SendTelegramMessage();
        $telegram->sendTelegramMessage($message);

        return response()->json(['status' => 'Message sent']);
    }

    private function formatGrafanaMessage(array $data)
    {
        $operator = $data['annotations']['operator'] ?? 'کلی';

        $time = '-';
        if (isset($data['annotations']['time'])) {
            try {
                $time = Carbon::parse($data['annotations']['time'])->setTimezone('Asia/Tehran')->toDateTimeString();
            } catch (\Exception $e) {
                // Log the error if necessary
            }
        }

        $rawHits = $data['annotations']['hits'] ?? $data['valueString'] ?? '';

        $hits = 'نامشخص';
        if (is_string($rawHits) && preg_match('/value=([0-9.]+)/', $rawHits, $matches)) {
            $hits = round((float)$matches[1]);
        }

        elseif (is_numeric($rawHits) || (is_string($rawHits) && is_numeric(trim($rawHits)))) {
            $hits = round((float)$rawHits);
        }

        return <<<EOT
        🚨 *افزایش خرید های ناموفق در {$operator}* 🚨
        *تعداد استفاده در ۱ دقیقه گذشته:* {$hits}
        *زمان:* {$time}
        EOT;
    }
}
