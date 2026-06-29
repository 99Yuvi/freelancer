<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('admin:id,name')
            ->when($request->admin_id, fn($q, $v) => $q->where('admin_id', $v))
            ->when($request->action,   fn($q, $v) => $q->where('action', 'like', "%{$v}%"))
            ->when($request->from,     fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to,       fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
