<?php

namespace App\Http\Controllers;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EskizController extends Controller
{
    public function index(Request $request): void
    {
        try {
            $data = $request->all();

            $requestId = $data['request_id'] ?? null;
            $status = strtoupper($data['status'] ?? '');
            $statusDate = $data['status_date'] ?? now();

            if (!$requestId) {
                Log::warning('[Eskiz] Callback received without request_id', $data);
                return;
            }

            $smsMessage = Message::where('request_id', $requestId)->first();

            if (!$smsMessage) {
                Log::warning("[Eskiz] SMS message with request_id {$requestId} not found.");
                return;
            }

            $internalStatus = match ($status) {
                'DELIVRD', 'DELIVERED', 'PARTDELIVERED' => MessageStatus::DELIVERED->value,
                default => MessageStatus::FAILED->value,
            };

            $smsMessage->update([
                'status' => $internalStatus,
                'delivered_at' => $statusDate,
            ]);

            Log::info("[Eskiz] Updated status for SMS {$smsMessage->id} to {$internalStatus}");
        } catch (\Throwable $e) {
            Log::error('[Eskiz] Error handling callback: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
        }
    }

}
