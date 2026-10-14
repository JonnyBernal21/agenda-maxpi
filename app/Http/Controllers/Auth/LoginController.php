<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = $request->user();

            if ($user?->role?->slug === Role::INSTRUCTOR) {
                return $this->handoffToInstructor($request, $user->email, $remember);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        if (Auth::guard('instructor')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('instructor.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
            ->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        Auth::guard('instructor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function handoffToInstructor(Request $request, string $email, bool $remember): RedirectResponse
    {
        $instructor = Instructor::query()->where('email', $email)->first();

        Auth::logout();

        if (! $instructor) {
            return back()
                ->withErrors([
                    'email' => 'Este usuario instructor no tiene una cuenta de instructor vinculada.',
                ])
                ->onlyInput('email');
        }

        Auth::guard('instructor')->login($instructor, $remember);
        $request->session()->regenerate();

        return redirect()->route('instructor.dashboard');
    }
}
