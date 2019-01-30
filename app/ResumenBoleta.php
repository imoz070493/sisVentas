<?php

namespace sisVentas;

use Illuminate\Database\Eloquent\Model;

class ResumenBoleta extends Model
{
    protected $table = 'resumen';

    protected $primaryKey = 'idresumen';

    public $timestamps = false;

    protected $fillable = [
    	'tipo',
    	'codigo',
    	'serie',
    	'numero',
    	'estado',
    	'hash',
    	'hash_cdr',
    	'mensaje',
        'ticket',
        'fecha_documento',
        'fecha'
    ];

    protected $guarded = [

    ];
}
