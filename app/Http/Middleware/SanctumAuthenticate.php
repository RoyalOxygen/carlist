<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SanctumAuthenticate
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  // public function handle(Request $request, Closure $next)
  // {
  public function handle(Request $request, Closure $next)
  {
    $user = Auth::guard('sanctum')->user();


    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    } else {
      return $next($request);
    }
  }
  // }
}
