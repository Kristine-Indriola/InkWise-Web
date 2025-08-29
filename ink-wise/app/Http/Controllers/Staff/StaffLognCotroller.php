<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffLoginController extends Controller
{
    protected function redirectTo()
{
    return view('staff.dashboard');
}

}
