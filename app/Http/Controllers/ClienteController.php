<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use sisVentas\Persona;
use Illuminate\Support\Facades\Redirect;
use sisVentas\Http\Requests\PersonaFormRequest;
use DB;

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
    	if($request)
    	{
    		$query = trim($request->get('searchText'));
    		$personas = DB::table('persona')
    			->where('nombre','LIKE','%'.$query.'%')
    			->where('tipo_persona','=','Cliente')
    			->orwhere('num_documento','LIKE','%'.$query.'%')
    			->where('tipo_persona','=','Cliente')
    			->orderBy('idpersona','desc')
    			->paginate(7);

            $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->orderBy('idrol','desc')
                ->get();

            $request->session()->put('permiso',$permiso);
            
    		return view('ventas.cliente.index',["personas"=>$personas,"searchText"=>$query]);
    	}

    }

    public function create()
    {
        if(isset($_GET['v'])){
            \Session::put('venta','1');
        }
    	return view("ventas.cliente.create");
    }

    public function store(PersonaFormRequest $request)
    {
    	$persona = new Persona;
    	$persona->tipo_persona = 'Cliente';
    	$persona->nombre = $request->get('nombre');
    	$persona->tipo_documento = $request->get('tipo_documento');
    	$persona->num_documento = $request->get('num_documento');
    	$persona->direccion = $request->get('direccion');
    	$persona->telefono = $request->get('telefono');
    	$persona->email = $request->get('email');
    	$persona->save();
        if(\Session::has('venta') && \Session::get('venta')=='1'){
            \Session::forget('venta');
            return Redirect::to('ventas/venta/create');    
        }else{
            return Redirect::to('ventas/cliente');            
        }
        

        // return Redirect::back();
    }

    public function show($id)
    {
    	return view("ventas.cliente.show",["persona"=>Persona::findOrFail($id)]);
    }

    public function edit($id)
    {
    	return view("ventas.cliente.edit",["persona"=>Persona::findOrFail($id)]);	
    }

    public function update(PersonaFormRequest $request, $id)
    {
    	$persona = Persona::findOrFail($id);
    	$persona->nombre = $request->get('nombre');
    	$persona->tipo_documento = $request->get('tipo_documento');
    	$persona->num_documento = $request->get('num_documento');
    	$persona->direccion = $request->get('direccion');
    	$persona->telefono = $request->get('telefono');
    	$persona->email = $request->get('email');
    	$persona->update();
    	return Redirect::to('ventas/cliente');
    }

    public function destroy($id)
    {
    	$persona = Persona::findOrFail($id);
    	$persona->tipo_persona = 'Inactivo';
    	$persona->update();
    	return Redirect::to('ventas/cliente');

    }
}
