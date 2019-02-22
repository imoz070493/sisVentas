<?php

namespace sisVentas\Http\Middleware;

use Closure;
use DB;

class PermisoConfiguracion
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
        $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->where('codigo','=','6')
                ->orderBy('idrol','desc')
                ->first();
        
        if($permiso){
            // dd('true');
            return $next($request);
        }
        else{
            // dd('false');
            return view('errors.inautorized');
        }
    }
}
