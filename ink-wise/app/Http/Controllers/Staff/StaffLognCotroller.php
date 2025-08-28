<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffLoginController extends Controller
{
    protected function redirectTo()
{
    return route('Staff.dashboard');
}

    public function logout()
    {
        return redirect()->route('Staff.dashboard');
    }
}
