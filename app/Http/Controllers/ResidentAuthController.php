<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRegistrationRequest;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ResidentAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::guard('resident')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->route('resident.dashboard');
    }

    public function register(ResidentRegistrationRequest $request): RedirectResponse
    {
        $resident = Resident::create($request->validated());
        Auth::guard('resident')->login($resident);
        $request->session()->regenerate();

        return redirect()->route('resident.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('resident')->logout();
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('resident.login');
    }
}
