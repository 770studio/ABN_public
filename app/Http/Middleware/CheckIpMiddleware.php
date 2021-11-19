<?php

namespace App\Http\Middleware;

use Closure;

class CheckIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public $whiteIps = [
        '178.207.89.30',
        '127.0.0.1',
        '178.204.13.155'
    ];
    public function handle($request, Closure $next)
    {
        if (!in_array($request->ip(), $this->whiteIps)) {


            return abort(404);
        }

        return $next($request);
    }
}
