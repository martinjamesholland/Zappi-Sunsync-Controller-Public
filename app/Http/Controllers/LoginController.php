<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Get credentials from environment
        $envUsername = config('services.sunsync.username');
        $envPassword = config('services.sunsync.password');

        // Check if credentials match environment variables
        if ($credentials['username'] === $envUsername && 
            $credentials['password'] === $envPassword) {
            
            // Get or create user for session management
            $user = User::firstOrCreate(
                ['email' => 'admin@solarsystem.local'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make($envPassword),
                ]
            );

            // Log the user in
            Auth::login($user, $request->boolean('remember'));

            // Regenerate session ID for security
            $request->session()->regenerate();

            // Redirect to intended page or home
            return redirect()->intended(route('home'));
        }

        // Authentication failed
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
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

