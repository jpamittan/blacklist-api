<?php

namespace App\Http\Middleware;

use Closure;

class OwnCorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $origin = isset($request->server()['HTTP_ORIGIN']) ?
            $request->server()['HTTP_ORIGIN'] : null;

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');

        return $next($request);
    }
}
