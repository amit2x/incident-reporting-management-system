<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super-admin');
    }

    /**
     * Display audit logs with filters.
     */
    public function index(Request $request)
    {
        $query = UserActivityLog::with('user')->recent();

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by IP
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', "%{$request->ip_address}%");
        }

        $logs = $query->paginate(30)->withQueryString();
        $users = User::orderBy('name')->get();

        // Get unique actions for filter dropdown
        $actions = UserActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs', compact('logs', 'users', 'actions'));
    }
}
