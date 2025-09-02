<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CostumerAuthController extends Controller
{
    public function showRegister() {
        return view('costumer.register');
    }

    public function showLogin() {
        return view('costumer.login');
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:costumers',
            'password' => 'required|min:6|confirmed',
        ]);

        $costumer = Costumer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::guard('costumer')->login($costumer);

        return redirect()->route('costumer.dashboard');
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('costumer')->attempt($credentials)) {
            return redirect()->route('costumer.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password']);
    }

    public function dashboard() {
        return view('costumer.dashboard', ['costumer' => Auth::guard('costumer')->user()]);
    }

    public function logout(Request $request) {
        Auth::guard('costumer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('costumer.login.form');
    }
}
