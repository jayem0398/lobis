<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private function getRole($user): string
    {
        return strtolower(trim($user->role ?? 'user'));
    }

    private function isAdmin($user): bool
    {
        $role = $this->getRole($user);
        return $role === 'admin' || $role === 'superadmin';
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$this->isAdmin($user)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized access. Activity logs are restricted to Administrators.',
            ], 403);
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}
