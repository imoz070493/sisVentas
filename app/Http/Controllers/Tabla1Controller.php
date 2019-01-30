<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use sisVentas\Http\Requests\Tabla1FormRequest;
use sisVentas\Tabla1;
use DB;

class Tabla1Controller extends Controller
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
    		$tablas = DB::table('tabla1')->where('column1','LIKE','%'.$query.'%')
    			->paginate(7);
    		return view('seguridad.tabla1.index',["tablas"=>$tablas,"searchText"=>$query]);
    	}

    }

    public function create()
    {
    	return view("seguridad.tabla1.create");
    }

    public function store(Tabla1FormRequest $request)
    {
    	$tabla1 = new Tabla1;
    	$tabla1->column1 = $request->get('column1');
    	$tabla1->column2 = $request->get('column2');
    	$tabla1->save();
    	return Redirect::to('seguridad/tabla1');
    }

    public function show($id)
    {
    	return view("almacen.categoria.show",["categoria"=>Categoria::findOrFail($id)]);
    }

    public function edit($id)
    {
    	return view("almacen.categoria.edit",["categoria"=>Categoria::findOrFail($id)]);	
    }

    public function update(CategoriaFormRequest $request, $id)
    {
    	$categoria = Categoria::findOrFail($id);
    	$categoria->nombre = $request->get('nombre');
    	$categoria->descripcion = $request->get('descripcion');
    	$categoria->update();
    	return Redirect::to('almacen/categoria');
    }

    public function destroy($id)
    {
    	$categoria = Categoria::findOrFail($id);
    	$categoria->condicion = '0';
    	$categoria->update();
    	return Redirect::to('almacen/categoria');

    }
}
