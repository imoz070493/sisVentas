<?php

namespace sisVentas;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'config';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
    	'ruc',
    	'razon_social',
    	'nombre_comercial',
    	'direccion',
    	'departamento',
    	'provincia',
    	'distrito',
    	'codpais',
        'ubigeo',
        'telefono',
        'correo',
        'usuario',
        'clave',
        'firma',
        'tipo',
        'estado'
    ];

    protected $guarded = [

    ];
}
