<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withTrashed()
            ->with(['freelancerProfile:id,user_id,verification_status,rating_avg', 'clientProfile:id,user_id,company_name'])
            ->when($request->q, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role,   fn($q, $v) => $q->where('role',   $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderBy($request->get('sort', 'created_at'), 'desc')
            ->paginate(25);

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json(['data' => $user->load(['freelancerProfile', 'clientProfile'])]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended'],
            'reason' => ['nullable', 'string'],
        ]);

        $user->update(['status' => $request->status]);

        AuditLog::record($request->user(), 'user.status_changed', $user, [
            'new_status' => $request->status,
            'reason'     => $request->reason,
        ]);

        return response()->json(['message' => "User {$request->status}.", 'data' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate(['reason' => ['required', 'string']]);

        $user->delete();

        AuditLog::record($request->user(), 'user.deleted', $user, ['reason' => $request->reason]);

        return response()->json(['message' => 'User account removed.']);
    }
}
