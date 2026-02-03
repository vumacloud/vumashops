<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhmcsApiKey
{
    /**
     * Handle an incoming request.
     *
     * Validates the WHMCS API key from the Authorization header or api_key parameter.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('services.whmcs.api_key');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WHMCS API key not configured on server',
            ], 500);
        }

        // Check Authorization header first (Bearer token)
        $providedKey = $request->bearerToken();

        // Fall back to api_key parameter (for WHMCS compatibility)
        if (empty($providedKey)) {
            $providedKey = $request->input('api_key');
        }

        // Also check X-API-Key header
        if (empty($providedKey)) {
            $providedKey = $request->header('X-API-Key');
        }

        if (empty($providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is required',
            ], 401);
        }

        if (!hash_equals($apiKey, $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key',
            ], 401);
        }

        return $next($request);
    }
}
