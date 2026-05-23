<?php
// app/Http/Controllers/Api/AuditLogController.php

namespace App\Http\Controllers\Api;

use App\Models\UserActivityLog;
use Illuminate\Http\Request;

class AuditLogController extends BaseApiController
{
    /**
     * List audit logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserActivityLog::with('user')->recent();

        // Filters
        if ($request->user_id) {
            $query->byUser($request->user_id);
        }

        if ($request->action) {
            $query->byAction($request->action);
        }

        if ($request->model_type) {
            $query->byModel($request->model_type, $request->model_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->ip_address) {
            $query->byIp($request->ip_address);
        }

        $logs = $query->paginate($request->get('per_page', 50));

        return $this->paginatedResponse($logs);
    }
}