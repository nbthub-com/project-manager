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
        // Simply send all users and managers for dashboard dispaly
        $users = User::where('role', 'user')->count();
        $managers = User::where('role', 'manager')->count();
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'user_count' => $users ??v0 ,
                'manager_count' => $managers ?? 0
                ]
            ]);
    }
    public function view_mems()
    {
        // Only send roles of manager and user
        $users = User::where('role', '!=', 'admin')->get();
        return Inertia::render('admin/Members', [
            'users' => $users
        ]);
    }
    public function add_mem(Request $request)
    {
        // Validate the request fully
        $request->validate([
            'name' => 'required|min:3|unique:users,name',
            'role' => 'required|in:user,manager',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);
        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);
        return redirect()->route('members.view')->with('success', 'Member added successfully!');
    }
}
