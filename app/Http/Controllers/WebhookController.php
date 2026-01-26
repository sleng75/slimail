<?php

namespace App\Http\Controllers;

use App\Services\Payment\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle CinetPay webhook.
     */
    public function cinetpay(Request $request, CinetPayService $cinetPayService)
    {
        Log::info('CinetPay webhook received', [
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $result = $cinetPayService->handleWebhook($request->all());

        if ($result['success']) {
            Log::info('CinetPay webhook processed successfully', [
                'payment_id' => $result['payment']->id ?? null,
                'status' => $result['status'] ?? null,
            ]);

            return response()->json(['status' => 'success']);
        }

        Log::warning('CinetPay webhook processing failed', [
            'error' => $result['error'] ?? 'Unknown error',
        ]);

        return response()->json(['status' => 'error', 'message' => $result['error']], 400);
    }
}
