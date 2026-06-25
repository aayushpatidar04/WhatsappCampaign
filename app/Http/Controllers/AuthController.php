<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('auth_logged_in')) {
            return redirect()->route('campaigns.index');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expectedUsername = config('app.auth_username');
        $expectedPassword = config('app.auth_password');

        if (empty($expectedUsername) || empty($expectedPassword)) {
            return redirect()->back()->with('error', 'Authentication not configured. Set AUTH_USERNAME and AUTH_PASSWORD in .env');
        }

        if ($request->username === $expectedUsername && $request->password === $expectedPassword) {
            session(['auth_logged_in' => true]);
            return redirect()->route('campaigns.index');
        }

        return redirect()->back()->with('error', 'Invalid credentials');
    }

    public function logout()
    {
        Session::forget('auth_logged_in');
        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}