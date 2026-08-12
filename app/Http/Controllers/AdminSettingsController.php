<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Display the admin settings page.
     */
    public function index()
    {
        return view('admin.settings.index');
    }
}
