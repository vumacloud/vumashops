<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TenantRegistrationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Note: With WHMCS integration, tenant creation is handled via WHMCS provisioning
        // This is kept for direct registration if needed
        return redirect()->route('home')->with('info', 'Please register through our billing portal.');
    }
}
