<?php

namespace BiztechEG\WhatsAppCloudApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWhatsAppWebhook
{
    /**
     * Handle an incoming request.
     * Verify that the webhook request comes from a trusted source.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $webhookSecret = config('whatsapp.webhook_secret');

        // If no secret is configured, skip verification (not recommended for production)
        if (!$webhookSecret) {
            return $next($request);
        }

        $signature = $request->header('X-WhatsApp-Signature');
        if (!$signature) {
            Log::warning('Missing webhook signature');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the raw payload.
        $payload = $request->getContent();

        // Compute the HMAC signature
        $computedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($computedSignature, $signature)) {
            Log::warning('Invalid webhook signature', [
                'computed' => $computedSignature,
                'provided' => $signature,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Sanitize the request data
        $sanitizedData = $this->sanitizeData($request->all());
        $request->merge($sanitizedData);

        return $next($request);
    }

    /**
     * Recursively sanitize incoming data.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            // Remove HTML tags and encode special characters.
            $value = filter_var($value, FILTER_SANITIZE_STRING);
        });
        return $data;
    }
}
