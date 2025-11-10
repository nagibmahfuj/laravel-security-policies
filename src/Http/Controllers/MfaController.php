<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MfaController extends Controller
{
    public function showVerify(Request $request)
    {
        return view('security-policies::mfa.verify');
    }

    public function verify(Request $request)
    {
        return redirect()->intended('/');
    }

    public function resend(Request $request)
    {
        return back();
    }
}
