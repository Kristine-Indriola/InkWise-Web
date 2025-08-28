<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class OwnerController extends Controller
{
    public function index()
    {
        return view('owner.owner-home'); // create a blade file for owner
    }
}


