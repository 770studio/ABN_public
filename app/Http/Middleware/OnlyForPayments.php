<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
class OnlyForPayments
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)    {


        if(Auth::user()->getRoleId() == 1 || Auth::user()->getRoleId() == 4 || Auth::user()->getRoleId() == 2 || Auth::user()->getRoleId() == 3){
            return $next($request);
        }
        else{

            return redirect()->back()->with('status', 403);
        }

    }
}
