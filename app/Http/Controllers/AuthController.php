<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Role-based redirect
            return match($user->role) {
                'admin'   => redirect()->route('dashboard'),
                'manager' => redirect()->route('los.pipeline'),
                'agent'   => redirect()->route('los.my-applications'),
                default   => redirect()->route('dashboard'),
            };
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Invalid credentials. Please check your email and password.']);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
