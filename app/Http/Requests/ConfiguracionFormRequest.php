<?php

namespace sisVentas\Http\Requests;

use sisVentas\Http\Requests\Request;

class ConfiguracionFormRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ruc' => 'required',
            'razon_social' => 'required',
            'nombre_comercial' => 'required',
            'direccion' => 'required',
            'departamento' => 'required',
            'provincia' => 'required',
            'distrito' => 'required',
            'codpais' => 'required',
            'ubigeo' => 'required',
            'telefono' => 'required',
            'correo' => 'required',
            'usuario' => 'required',
            'clave' => 'required',
            'firma' => 'max:256'
        ];
    }
}
