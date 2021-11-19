<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            if(Auth::user()->getRoleId() == 1 || Auth::user()->getRoleId() == 2){
                return redirect()->route('contracts');
            }
            elseif (Auth::user()->getRoleId() == 3){
                return redirect()->route('subagents');
            }
            elseif (Auth::user()->getRoleId() == 4){
                return redirect()->route('payments');
            }
            elseif (Auth::user()->getRoleId() == 5){
                return redirect()->route('consultants_schedule.index');
            }
        }

        return $next($request);
    }
}
