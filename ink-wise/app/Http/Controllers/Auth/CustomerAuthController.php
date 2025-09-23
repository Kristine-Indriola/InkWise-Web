<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer.register');
    }

    public function showLogin()
    {
        return view('customer.login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        //1️⃣ Create the user (login credentials)
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        // 2️⃣ Create the customer profile (extra info)
        $customer = Customer::create([
            'user_id' => $user->user_id, // FK to users table
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'contact_number' => $request->contact_number,
        ]);

        // 3️⃣ Login customer
        auth()->login($user);


        return redirect()->route('customer.dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('customer.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password']);
    }

    public function dashboard()
    {
        return view('customer.dashboard', [
            'customer' => Auth::user()->customer // eager load profile
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.dashboard');
    }
}