<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

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

            $smsMessage = Message::where('external_id', $requestId)->first();

            if (!$smsMessage) {
                Log::warning("[Eskiz] SMS message with external_id {$requestId} not found.");
                return;
            }

            $internalStatus = match ($status) {
                'DELIVRD', 'DELIVERED', 'PARTDELIVERED' => History::STATUS_DELIVERED,
                'REJECTED', 'UNDELIV', 'UNDELIVERABLE', 'EXPIRED', 'REJECTD', 'DELETED' => History::STATUS_FAILED,
                'NEW', 'STORED', 'ACCEPTED', 'ENROUTE', 'UNKNOWN' => History::STATUS_SENT, // можно адаптировать по логике
                default => History::STATUS_SENT,
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
