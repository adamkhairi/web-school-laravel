<?php

namespace App\Http\Middleware;

use Closure;
use App\Enums\RoleType;

class CheckRole
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle($request, Closure $next, RoleType $role)
  {
    if (!$request->user() || !$request->user()->hasRole($role)) {
      abort(403, "Unauthorized action.");
    }
    return $next($request);
  }
}
