<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use sisVentas\Http\Requests\ArticuloFormRequest;
use sisVentas\Articulo;
use DB;

use sisVentas\User;

class ReporteController extends Controller
{

    protected $compras;
    protected $ventas;

    public function __construct()
    {
        $this->compras = '';
        $this->ventas = '';
        $this->middleware('auth');
        $this->middleware('permisoReportes');
    }

    public function index(Request $request)
    {
    	

    }

    public function create()
    {
    	
    }

    public function store(ArticuloFormRequest $request)
    {
    	
    }

    public function show($id)
    {
    	
    }

    public function edit($id)
    {
    	
    }

    public function update(ArticuloFormRequest $request, $id)
    {
    	
    }

    public function destroy($id)
    {

    }

    public function obtenerCompras(Request $request)
    {
        if($request){
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin = $request->get('fecha_fin');

            $nuevaFechaInicio = date('Y-m-d',strtotime($fecha_inicio));
            $nuevaFechaFin = date('Y-m-d',strtotime($fecha_fin));

            $nuevaFechaInicio = $nuevaFechaInicio.' 00:00:00';
            $nuevaFechaFin = $nuevaFechaFin.' 23:59:59';

            LOG::info($nuevaFechaInicio." - ".$nuevaFechaFin);

            $compras = DB::table('ingreso as i')
                ->join('persona as p','i.idproveedor','=','p.idpersona')
                ->join('detalle_ingreso as di','i.idingreso','=','di.idingreso')
                ->select('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.num_comprobante','i.impuesto','i.estado','total_compra')
                ->whereBetween('i.fecha_hora',[$nuevaFechaInicio,$nuevaFechaFin])
                ->orderBy('i.idingreso','desc')
                ->groupBy('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.impuesto','i.estado')
                ->get();

        }
        return view('reportes.compras',["compras"=>$compras,"fecha_inicio"=>$fecha_inicio,"fecha_fin"=>$fecha_fin]);
    }

    public function obtenerVentas(Request $request)
    {
        if($request){
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin = $request->get('fecha_fin');

            $nuevaFechaInicio = date('Y-m-d',strtotime($fecha_inicio));
            $nuevaFechaFin = date('Y-m-d',strtotime($fecha_fin));

            $nuevaFechaInicio = $nuevaFechaInicio.' 00:00:00';
            $nuevaFechaFin = $nuevaFechaFin.' 23:59:59';

            LOG::info($nuevaFechaInicio." - ".$nuevaFechaFin);

            $ventas = DB::table('venta as v')
                ->join('persona as p','v.idcliente','=','p.idpersona')
                ->select('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.impuesto','v.estado','v.total_venta','v.response_code')
                ->whereBetween('v.fecha_hora',[$nuevaFechaInicio,$nuevaFechaFin])
                ->orderBy('v.idventa','des')
                ->groupBy('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.impuesto','v.estado')
                ->get();

        }
        return view('reportes.ventas',["ventas"=>$ventas,"fecha_inicio"=>$fecha_inicio,"fecha_fin"=>$fecha_fin]);
    }

    public function exportarExcelVentas(){
        $fecha_inicio = $_GET['fi'];
        $fecha_fin = $_GET['ff'];    

        $nuevaFechaInicio = date('Y-m-d',strtotime($fecha_inicio));
        $nuevaFechaFin = date('Y-m-d',strtotime($fecha_fin));

        $nuevaFechaInicio = $nuevaFechaInicio.' 00:00:00';
        $nuevaFechaFin = $nuevaFechaFin.' 23:59:59';

        LOG::info($nuevaFechaInicio." - ".$nuevaFechaFin);

        $this->ventas = DB::table('venta as v')
                ->join('persona as p','v.idcliente','=','p.idpersona')
                ->select('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.impuesto','v.estado','v.total_venta','v.response_code')
                ->whereBetween('v.fecha_hora',[$nuevaFechaInicio,$nuevaFechaFin])
                ->orderBy('v.idventa','des')
                ->groupBy('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.impuesto','v.estado')
                ->get();



        \Excel::create('Ventas '.date('Y-m-d',strtotime($fecha_inicio)).' a '.date('Y-m-d',strtotime($fecha_fin)), function($excel) {
     
            // $users = User::all();
            $ventas = $this->ventas;
            $excel->sheet('Ventas', function($sheet) use($ventas) {
                //MODO 1:
                // $sheet->fromArray($users);
                //set general font style
                $sheet->setStyle(array(
                    'font' => array(
                        'name'      =>  'Calibri',
                        'size'      =>  15,
                        'bold'      =>  false,
                    )
                ));
                //set background to headers
                $sheet->cells('A1:I1', function($cells) {
 
                    $cells->setBackground('#000000')
                            ->setFontColor('#ffffff');
                    //set other properties
                });
                // $sheet->row(1,['Numero','Fe','Email','Fecha de Creacion','Fecha de Actualizacion']);
                // foreach ($users as $index => $user) {
                //     $sheet->row($index+2,[$user->id,$user->name,$user->email,$user->created_at,$user->updated_at]);
                // }

                $sheet->row(1,['Numero','Fecha Hora','Cliente','Tipo Comprobante','Serie Comprobante', 'Numero Comprobante',' Estado', 'Impuesto', 'Total Compra']);
                $total = 0;
                $impuesto = 0;
                foreach ($ventas as $index => $venta) {
                    $sheet->row($index+2,[$venta->idventa,$venta->fecha_hora,$venta->nombre,$venta->tipo_comprobante,$venta->serie_comprobante, $venta->num_comprobante, $venta->estado=='A'?'Aceptado':'Cancelado',($venta->total_venta - $venta->total_venta/1.18),$venta->total_venta]);
                    $total = $total + $venta->total_venta;
                    $impuesto = $impuesto + ($venta->total_venta - $venta->total_venta/1.18);
                }
                $sheet->mergeCells('F'.(count($ventas)+2).':G'.(count($ventas)+2));
                $sheet->cells('F'.(count($ventas)+2).':I'.(count($ventas)+2), function($cells) {
 
                    $cells->setBackground('#000000')
                            ->setFontColor('#ffffff');
                    //set other properties
                });
                $sheet->row((count($ventas)+2),['','','','','', 'TOTAL','', $impuesto, $total]);
            });
     
        })->export('xlsx');
    }

    public function exportarExcelCompras(){
        $fecha_inicio = $_GET['fi'];
        $fecha_fin = $_GET['ff'];    

        $nuevaFechaInicio = date('Y-m-d',strtotime($fecha_inicio));
        $nuevaFechaFin = date('Y-m-d',strtotime($fecha_fin));

        $nuevaFechaInicio = $nuevaFechaInicio.' 00:00:00';
        $nuevaFechaFin = $nuevaFechaFin.' 23:59:59';

        LOG::info($nuevaFechaInicio." - ".$nuevaFechaFin);

        $this->compras = DB::table('ingreso as i')
            ->join('persona as p','i.idproveedor','=','p.idpersona')
            ->join('detalle_ingreso as di','i.idingreso','=','di.idingreso')
            ->select('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.num_comprobante','i.impuesto','i.estado','total_compra')
            ->whereBetween('i.fecha_hora',[$nuevaFechaInicio,$nuevaFechaFin])
            ->orderBy('i.idingreso','desc')
            ->groupBy('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.impuesto','i.estado')
            ->get();



        \Excel::create('Compras '.date('Y-m-d',strtotime($fecha_inicio)).' a '.date('Y-m-d',strtotime($fecha_fin)), function($excel) {
     
            // $users = User::all();
            $compras = $this->compras;
            $excel->sheet('Compras', function($sheet) use($compras) {
                //MODO 1:
                // $sheet->fromArray($users);
                //set general font style
                $sheet->setStyle(array(
                    'font' => array(
                        'name'      =>  'Calibri',
                        'size'      =>  15,
                        'bold'      =>  false,
                    )
                ));
                //set background to headers
                $sheet->cells('A1:I1', function($cells) {
 
                    $cells->setBackground('#000000')
                            ->setFontColor('#ffffff');
                    //set other properties
                });
                // $sheet->row(1,['Numero','Fe','Email','Fecha de Creacion','Fecha de Actualizacion']);
                // foreach ($users as $index => $user) {
                //     $sheet->row($index+2,[$user->id,$user->name,$user->email,$user->created_at,$user->updated_at]);
                // }

                $sheet->row(1,['Numero','Fecha Hora','Nombre','Tipo Comprobante','Serie Comprobante', 'Numero Comprobante',' Estado', 'Impuesto', 'Total Compra']);
                $total = 0;
                $impuesto = 0;
                foreach ($compras as $index => $compra) {
                    $sheet->row($index+2,[$compra->idingreso,$compra->fecha_hora,$compra->nombre,$compra->tipo_comprobante,$compra->serie_comprobante, $compra->num_comprobante, $compra->estado=='A'?'Aceptado':'Cancelado',($compra->total_compra - $compra->total_compra/1.18),$compra->total_compra]);
                    $total = $total + $compra->total_compra;
                    $impuesto = $impuesto + ($compra->total_compra - $compra->total_compra/1.18);
                }
                $sheet->mergeCells('F'.(count($compras)+2).':G'.(count($compras)+2));
                $sheet->cells('F'.(count($compras)+2).':I'.(count($compras)+2), function($cells) {
 
                    $cells->setBackground('#000000')
                            ->setFontColor('#ffffff');
                    //set other properties
                });
                $sheet->row((count($compras)+2),['','','','','', 'TOTAL','', $impuesto, $total]);
            });
     
        })->export('xlsx');
    }
}
