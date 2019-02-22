<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use sisVentas\Marca;
use Illuminate\Support\Facades\Redirect;
use sisVentas\Http\Requests\MarcaFormRequest;
use DB;

class MarcaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permisoAlmacen');
    }

    public function index(Request $request)
    {
    	if($request)
    	{
    		$query = trim($request->get('searchText'));
    		$marcas = DB::table('marca')->where('nombre','LIKE','%'.$query.'%')
    			->where('condicion','=','1')
    			->orderBy('idmarca','desc')
    			->paginate(7);

            $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->orderBy('idrol','desc')
                ->get();

            $request->session()->put('permiso',$permiso);
                        
    		return view('almacen.marca.index',["marcas"=>$marcas,"searchText"=>$query]);
    	}

    }

    public function create()
    {
    	return view("almacen.marca.create");
    }

    public function store(MarcaFormRequest $request)
    {
    	$marca = new Marca;
    	$marca->nombre = $request->get('nombre');
    	$marca->descripcion = $request->get('descripcion');
    	$marca->condicion = '1';
    	$marca->save();
    	return Redirect::to('almacen/marca');
    }

    public function show($id)
    {
    	return view("almacen.categoria.show",["categoria"=>Categoria::findOrFail($id)]);
    }

    public function edit($id)
    {
    	return view("almacen.marca.edit",["marca"=>Marca::findOrFail($id)]);
    }

    public function update(MarcaFormRequest $request, $id)
    {
    	$marca = Marca::findOrFail($id);
    	$marca->nombre = $request->get('nombre');
    	$marca->descripcion = $request->get('descripcion');
    	$marca->update();
    	return Redirect::to('almacen/marca');
    }

    public function destroy($id)
    {
    	$marca = Marca::findOrFail($id);
    	$marca->condicion = '0';
    	$marca->update();
    	return Redirect::to('almacen/marca');

    }
}
