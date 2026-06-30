<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\LoginPortalResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InstructorLoginController extends Controller
{
    public function create(): View
    {
        return view('instructor.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $portal = LoginPortalResolver::resolve($credentials['email']);

        if ($portal && $portal['key'] !== 'instructor') {
            return back()
                ->withErrors([
                    'email' => "Este correo pertenece al portal de {$portal['label']}. Usa el enlace correcto abajo.",
                ])
                ->with('suggested_login_url', $portal['login_route'])
                ->onlyInput('email');
        }

        if (! Auth::guard('instructor')->attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('instructor.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('instructor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('instructor.login');
    }
}
