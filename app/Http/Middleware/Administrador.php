<?php

namespace sisVentas\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Closure;
use Session;
use DB;

class Administrador
{

    protected $auth;

    public function __construct(Guard $auth){
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // switch ($this->auth->user()->idrol) {
        //     case '1':
        //         # Administrador
        //         return redirect()->to('admin');
        //         break;
        //     case '1':
        //         # Responsable
        //         return redirect()->to('responsable');
        //         break;
        //     default:
        //         return redirect()->to('login');
        //         break;
        // }
        // dd($this->auth->user()->idrol);
        $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->where('codigo','=','2')
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
