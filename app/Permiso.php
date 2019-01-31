<?php

namespace sisVentas;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'Permiso';

    protected $primaryKey = 'idpermiso';

    public $timestamps = false;

    protected $fillable = [
    	'nombre',
    	'url',
    	'idrol',
    ];

    protected $guarded = [

    ];
}
