<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('auth/login');
});

Route::resource('almacen/categoria','CategoriaController');

// Route::match(['get', 'post'], 'venta/peticion', 'VentaController@peticion');

Route::post('venta/peticion', 'VentaController@peticion');

Route::resource('venta/pdf', 'VentaController@crearPDF');

Route::resource('almacen/articulo','ArticuloController');

Route::resource('ventas/cliente','ClienteController');

Route::resource('compras/proveedor','ProveedorController');

Route::resource('compras/ingreso','IngresoController');

Route::resource('ventas/venta','VentaController');

Route::resource('seguridad/usuario','UsuarioController');

Route::resource('seguridad/configuracion','ConfiguracionController');

Route::auth();

Route::resource('almacen/marca','MarcaController');


Route::get('/home', 'HomeController@index');

Route::get('/{slug?}', 'HomeController@index');
