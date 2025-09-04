<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        // Simply send all users and managers for dashboard display
        $users = User::where('role', 'user')->count();
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'user_count' => $users ?? 0,
            ],
        ]);
    }

    public function view_mems()
    {
        // Only send roles of manager and user
        $users = User::where('role', '!=', 'admin')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email']); 

        return Inertia::render('admin/Members', [
            'users' => $users,
        ]);
    }
    public function add_mem(Request $request)
    {
        // Normalize values before validation
        $request->merge([
            'name' => strtolower($request->name),
            'email' => strtolower($request->email),
            'role' => strtolower($request->role),
        ]);

        // Validate with case-insensitive uniqueness
        $validated = $request->validate([
            'name' => [
                'required',
                'min:3',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [$value])->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(email) = ?', [$value])->exists()) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ],
            'role' => 'required|in:user',
            'password' => 'required|min:6',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'user' => $user,
        ], 201);
    }
    public function search($query)
    {
        $query = strtolower($query);
        if(!$query){
            return redirect()->route('members.view');
        }
        $users = User::where('role', '!=', 'admin')
            ->where(function($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$query}%"]);
            })
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email', 'role']);

        return response()->json($users);
    }
}
