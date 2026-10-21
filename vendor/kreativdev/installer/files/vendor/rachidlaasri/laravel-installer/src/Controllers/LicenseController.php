<?php

namespace RachidLaasri\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;

class LicenseController extends Controller
{
    public function license()
    {
        return redirect()->route('LaravelInstaller::environmentWizard');
    }

    public function licenseCheck()
    {
        return redirect()->route('LaravelInstaller::environmentWizard');
    }
}
