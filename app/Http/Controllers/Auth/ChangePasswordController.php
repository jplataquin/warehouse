<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the form to change password.
     */
    public function showChangeForm()
    {
        return view('auth.change-password');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = auth()->user();
        
        // Update user password and set needs_password_change to false
        $user->update([
            'password' => Hash::make($request->password),
            'needs_password_change' => false,
        ]);

        return redirect()->route('home')->with('success', 'Your password has been changed successfully.');
    }
}
