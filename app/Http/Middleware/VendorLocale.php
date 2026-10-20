<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VendorLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $vendorId = auth('vendor')->id();
        $sessionKey = 'vendor_lang_' . $vendorId;

        if (Session::has($sessionKey)) {
            app()->setLocale(Session::get($sessionKey));
        } else {
            app()->setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
