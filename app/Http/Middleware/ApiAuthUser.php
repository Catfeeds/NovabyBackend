<?php

namespace App\Http\Middleware;

use Closure;
use DB;
use App\Model\User;
class ApiAuthUser
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

        $response = $next($request);

        //$response->header('Access-Control-Allow-Origin', '*');
        //$response->header('Access-Control-Allow-Headers', 'token,DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');
        //$response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
        //$response->header('Access-Control-Allow-Credentials', 'true');
        //dd($request->get('token',''));
        //$token = $request->header("token");
        $token = $request->header('token')?$request->header('token'):$request->get('token');

        if(!$token){
            return response()->json(['code'=>-2,'msg'=>'not login ','data'=>[]]);
        }

        $user = User::where('user_token',$token)->first();

        if(!$user){
            return response()->json(['code'=>-2,'msg'=>'auth failed ','data'=>[]]);
        }
        return $response;
    }
}
