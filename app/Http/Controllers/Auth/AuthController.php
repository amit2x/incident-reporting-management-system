// app/Http/Controllers/Auth/AuthController.php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user is active
            if ($user->status !== 'active') {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated. Contact administrator.'],
                ]);
            }

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log('User logged in');

            $request->session()->regenerate();

            if ($request->ajax()) {
                return $this->successResponse([
                    'redirect' => route('dashboard'),
                ], 'Login successful');
            }

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'employee_id' => $request->employee_id,
            'department_id' => $request->department_id,
            'designation' => $request->designation,
            'status' => 'active',
        ]);

        // Assign default role
        $user->assignRole('staff');

        // Send email verification
        $user->sendEmailVerificationNotification();

        Auth::login($user);

        if ($request->ajax()) {
            return $this->successResponse([
                'redirect' => route('dashboard'),
            ], 'Registration successful');
        }

        return redirect()->route('dashboard')->with('success', 'Registration successful! Please verify your email.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log activity
        if ($user) {
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('User logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax()) {
            return $this->successResponse([
                'redirect' => route('login'),
            ], 'Logged out successfully');
        }

        return redirect()->route('login');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['success' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
