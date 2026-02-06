<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $payload = $request->all();
            
            $this->paymentService->handleWebhook($payload);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify Paystack webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Paystack-Signature');
        
        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $request->getContent(), config('paystack.secretKey'));

        return hash_equals($computedSignature, $signature);
    }
}
