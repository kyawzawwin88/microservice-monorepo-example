<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventLogController extends Controller
{
    /**
     * List all event logs (paginated).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            EventLog::orderBy('created_at', 'desc')->paginate(50)
        );
    }
}
