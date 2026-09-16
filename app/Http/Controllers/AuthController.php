<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Process login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        try {
            if (Auth::attempt($credentials, $remember)) {

                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }

        } catch (QueryException $e) {

            // Log the real error for debugging (storage/logs/laravel.log)
            report($e);

            return back()
                ->withErrors([
                    'email' => 'Unable to connect to the database. Please try again later or contact the administrator.',
                ])
                ->onlyInput('email');
        }

        return back()
            ->withErrors([
                'email' => 'The email or password is incorrect.',
            ])
            ->onlyInput('email');
    }

    /**
     * Show registration form.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Process registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],

                'email' => $validated['email'],

                'password' => Hash::make($validated['password']),
            ]);

            Auth::login($user);

            $request->session()->regenerate();

            return redirect()->route('dashboard');

        } catch (QueryException $e) {

            report($e);

            return back()
                ->withErrors([
                    'email' => 'Unable to connect to the database. Please try again later or contact the administrator.',
                ])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}