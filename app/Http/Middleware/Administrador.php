<?php

namespace sisVentas\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Closure;
use Session;

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
        if($this->auth->user()->idrol=='1'){
            return $next($request);    
        }else{
            return view('errors.inautorized');
        }

    
        
    }
}
