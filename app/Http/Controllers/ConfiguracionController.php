<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use sisVentas\Configuracion;
use Illuminate\Support\Facades\Redirect;
use sisVentas\Http\Requests\ConfiguracionFormRequest;
use DB;

class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permisoConfiguracion');
    }

    public function index(Request $request)
    {
        if($request)
        {
            $query = trim($request->get('searchText'));
            $categorias = DB::table('config')
                ->paginate(7);

            $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->orderBy('idrol','desc')
                ->get();

            $request->session()->put('permiso',$permiso);

            return view('seguridad.configuracion.index',["perfiles"=>$categorias,"searchText"=>$query]);
        }

    }

    public function create()
    {
        return view("almacen.categoria.create");
    }

    public function store(CategoriaFormRequest $request)
    {
        // $categoria = new Categoria;
        // $categoria->nombre = $request->get('nombre');
        // $categoria->descripcion = $request->get('descripcion');
        // $categoria->condicion = '1';
        // $categoria->save();
        // return Redirect::to('almacen/categoria');
    }

    public function show($id)
    {
        // return view("almacen.categoria.show",["categoria"=>Categoria::findOrFail($id)]);
    }

    public function edit($id)
    {
        return view("seguridad.configuracion.edit",["perfil"=>Configuracion::findOrFail($id)]);
    }

    public function update(ConfiguracionFormRequest $request, $id)
    {
        $configuracion = Configuracion::findOrFail($id);
        $configuracion->ruc = $request->get('ruc');
        $configuracion->razon_social = $request->get('razon_social');
        $configuracion->nombre_comercial = $request->get('nombre_comercial');
        $configuracion->direccion = $request->get('direccion');
        $configuracion->departamento = $request->get('departamento');
        $configuracion->provincia = $request->get('provincia');
        $configuracion->distrito = $request->get('distrito');
        $configuracion->codpais = $request->get('codpais');
        $configuracion->ubigeo = $request->get('ubigeo');
        $configuracion->telefono = $request->get('telefono');
        $configuracion->correo = $request->get('correo');
        $configuracion->usuario = $request->get('usuario');
        $configuracion->clave = $request->get('clave');
        $configuracion->update();
        return Redirect::to('seguridad/configuracion');
    }

    public function destroy($id)
    {
        // $categoria = Categoria::findOrFail($id);
        // $categoria->condicion = '0';
        // $categoria->update();
        // return Redirect::to('almacen/categoria');
    }
}
