<?php

namespace sisVentas;

use Illuminate\Database\Eloquent\Model;

class Tabla1 extends Model
{
    protected $table ='tabla1';
    protected $primaryKey = 'idtabla1';
    protected $timestamps=false;

    protected $fillable = [
    	'idtabla1',
    	'column1',
    	'column2'
    ];

    protected $guarded = [
    	
    ];
}
