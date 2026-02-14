<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    /**
     * Health check endpoint.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'invoice-service',
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
