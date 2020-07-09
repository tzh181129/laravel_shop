<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Firebase\JWT\JWT;

class Check
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
//        if($request['token']){
//
//        }
        return $next($request);
    }

//    /**
//     *
//     */
//
//    /**
//     * 刷新token
//     */
//    public function refresh(){
//
//    }


}
