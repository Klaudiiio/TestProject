<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get admin dashboard data.
     */
    public function dashboard()
    {
        return response()->json([
            'message' => 'Welcome to Admin Dashboard',
            'data' => [
                'total_users' => \App\Models\User::count(),
                'users_by_role' => \App\Models\User::selectRaw('role, count(*) as count')
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role')
            ]
        ]);
    }
}