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
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|min:6|confirmed',
            'birthdate'      => 'required|date',
            'contact_number' => 'nullable|string|max:20',
            'middle_name'    => 'nullable|string|max:255',
        ]);

        // 1. Create the User (for authentication)
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
        ]);

        // 2. Create the Customer profile
        Customer::create([
            'user_id'        => $user->user_id,
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->contact_number,
            'date_of_birth'  => $request->birthdate,
        ]);


        Auth::login($user);

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
            'customer' => Auth::user()?->customer, // safe access
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard'); // 👈 check if this route exists
    }

    public function uploadDesign(Request $request)
    {
        $request->validate([
            'design_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($request->hasFile('design_file')) {
            $file = $request->file('design_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('customer_uploads', $filename, 'public');

            // You can save to database or session for later use
            // For now, redirect to design edit page with the uploaded image
            return redirect()->route('design.edit')->with('uploaded_image', $path);
        }

        return back()->withErrors(['design_file' => 'Upload failed.']);
    }
}
