<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use sisVentas\Http\Requests\ResumenBoletaFormRequest;
use sisVentas\Venta;
use sisVentas\DetalleVenta;
use DB;

use sisVentas\Http\Controllers\Core;
use sisVentas\Http\Controllers\Util;

use Carbon\Carbon;
use Response;
use Illuminate\Support\Collection;

class ResumenBoletaController extends Controller
{
    public function __construct()
    {
    	// $this->middleware('auth');
    }

    public function index(Request $request)
    {

        if($request)
    	{
    		$query = trim($request->get('searchText'));
    		$resumen = DB::table('resumen')
    			->select('*')
    			->where('codigo','=','RC')
    			->orderBy('idresumen','desc')
    			->groupBy('idresumen','ticket')
    			->get();

    		return view('ventas.resumenboleta.index',["resumen"=>$resumen,"searchText"=>$query]);
    	}

    }

    public function create()
    {
        $comprobantes = DB::table('venta as v')
                ->join('persona as p','v.idcliente','=','p.idpersona')
                ->join('detalle_venta as dv','v.idventa','=','dv.idventa')
                ->select('v.idventa','v.fecha_hora','p.idpersona','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.impuesto','v.estado','v.total_venta','v.response_code','v.moneda','v.tipo_comprobante')
                ->where('v.tipo_comprobante','=','01')
                ->orWhere('v.tipo_comprobante','=','03')
                ->orderBy('v.idventa','desc')
                ->groupBy('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.impuesto','v.estado')
                ->get();
    	$personas = DB::table('persona')->where('tipo_persona','=','Cliente')->get();
    	$articulos = DB::table('articulo as art')
    		->join('detalle_ingreso as di','art.idarticulo','=','di.idarticulo')
    		->select(DB::raw('CONCAT(art.codigo," ",art.nombre) AS articulo'),'art.idarticulo','art.stock',DB::raw('avg(di.precio_venta) as precio_promedio'))
    		->where('art.estado','=','Activo')
    		->where('art.stock','>','0')
    		->groupBy('articulo','art.idarticulo','art.stock')
    		->get();
    	return view("ventas.notas.create",["personas"=>$personas,'articulos'=>$articulos, 'comprobantes'=>$comprobantes]);
    }

    public function store(VentaFormRequest $request)
    {

    	try{
    		DB::beginTransaction();
    			$venta = new Venta;
    			$venta->idcliente = $request->get('idcliente');
    			$venta->tipo_comprobante = $request->get('tipo_comprobante');
    			$venta->serie_comprobante = $request->get('serie_comprobante');
    			$venta->num_comprobante = $request->get('num_comprobante');
    			$venta->total_venta = $request->get('total_venta');

                $venta->docmodifica_tipo = $request->get('tipodoc');
                $venta->docmodifica = $request->get('smodifica')."-".$request->get('nmodifica');
                $venta->modifica_motivo = $request->get('motivo');
                $venta->modifica_motivod = $request->get('motivod');
                $venta->moneda = $request->get('moneda');

    			$mytime = Carbon::now('America/Lima');
    			$venta->fecha_hora = $mytime->toDateTimeString();
    			$venta->impuesto = '18';
    			$venta->estado = 'A';
    			$venta->save();

    			$idarticulo = $request->get('idarticulo');
    			$cantidad = $request->get('cantidad');
    			$descuento = $request->get('descuento');
    			$precio_venta = $request->get('precio_venta');

    			$cont = 0;
    			while($cont < count($idarticulo)){
    				$detalle = new DetalleVenta();
    				$detalle->idventa = $venta->idventa;
    				$detalle->idarticulo = $idarticulo[$cont];
    				$detalle->cantidad = $cantidad[$cont];
    				$detalle->descuento = $descuento[$cont];
    				$detalle->precio_venta = $precio_venta[$cont];
    				$detalle->save();
    				$cont = $cont + 1;
    			}

    		DB::commit();
    	}catch(\Exception $e){
    		DB::rollback();
            LOG::info($e);
    	}

        $idcliente = $request->get('idcliente');
        $tipo_comprobante = $request->get('tipo_comprobante');
        $serie_comprobante = $request->get('serie_comprobante');
        $num_comprobante = $request->get('num_comprobante');
        $total_venta = $request->get('total_venta');
        $util = new Util\UtilHelper();
        $leyenda = $util->numtoletras($total_venta);

        $mytime = Carbon::now('America/Lima');
        $fecha = $mytime->toDateString();
        $hora = $mytime->toTimeString();

        $idarticulo = $request->get('idarticulo');
        $cantidad = $request->get('cantidad');
        $descuento = $request->get('descuento');
        $precio_venta = $request->get('precio_venta');

        $empresa = DB::table('config')
            ->where('estado','=','1')
            ->first();

        $creditNote = new Core\CreditNote();
        $creditNote->buildCreditNoteXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta, $empresa->ruc);

        $factura = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
        $creditNote->enviarFactura($factura);
        $path = public_path('cdn/cdr\R-'.$factura.'.ZIP');
        LOG::info($path);
        $responseCdr = $creditNote->readCdr('',$path,$tipo_comprobante);

        $v = Venta::findOrFail($venta->idventa);
        $v->response_code=$responseCdr['code'];
        $v->descripcion_code=$responseCdr['message'];
        $v->update();

        $cliente = DB::table('persona as per')
            ->join('venta as v','per.idpersona','=','v.idcliente')
            ->select('per.nombre','per.direccion','per.num_documento','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante')
            ->where('v.idventa','=',$venta->idventa)
            ->first();

        $items = DB::table('venta as v')
            ->join('detalle_venta as dv','dv.idventa','=','v.idventa')
            ->join('articulo as art','art.idarticulo','=','dv.idarticulo')
            ->select('art.codigo','art.nombre','dv.cantidad','dv.precio_venta',DB::raw('dv.cantidad * dv.precio_venta AS total'))
            ->where('v.idventa','=',$venta->idventa)
            ->get();
        
        $creditNote->crearPDF($empresa,$cliente,$items, $leyenda);


    	return Redirect::to('ventas/notas');
    }

    public function crearPDF(){
        $invoice = new Core\Invoice();
        $invoice->crearPDF();
    }

    public function show($id)
    {
    	$venta =DB::table('venta as v')
			->join('persona as p','v.idcliente','=','p.idpersona')
			->join('detalle_venta as dv','v.idventa','=','dv.idventa')
			->select('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.impuesto','v.estado','v.total_venta')
			->where('v.idventa','=',$id)
			->first();

    	$detalles = DB::table('detalle_venta as d')
			->join('articulo as a','d.idarticulo','=','a.idarticulo')
			->select('a.nombre as articulo','d.cantidad','d.descuento','d.precio_venta')
			->where('d.idventa','=',$id)
			->get();

    	return view("ventas.venta.show",["venta"=>$venta,"detalles"=>$detalles]);
    }

    public function destroy($id){
    	$venta = Venta::findOrFail($id);
    	$venta->Estado='C';
    	$venta->update();
    	return Redirect::to('ventas/venta');
    }

    public function peticion(Request $request){
        $tipo_comprobante = $_POST['tipoComprobante'];
        $serie = "F001";    

        $ventas = DB::table('venta')
                ->select('*')
                ->where('serie_comprobante','LIKE','%'.$serie.'%')
                ->where('tipo_comprobante','=',$tipo_comprobante)
                ->orderBy('idventa','desc')
                ->first();
        if(is_null($ventas)){
            $num_comprobante=0;
        }else{
            $num_comprobante = $ventas->num_comprobante;
        }
        return response()->json([
            'serie' => $serie,
            'correlativo' => str_pad($num_comprobante + 1,  8, "0", STR_PAD_LEFT),
            // 'ventas'=>$ventas
        ]);
    }

    public function detalle(Request $request){
        $tipo_comprobante = $_POST['tipoComprobante'];
        $serie = $_POST['serie'];
        $num_comprobante = $_POST['num_comprobante'];

        $ventas = DB::table('venta as v')
                ->join('detalle_venta as dv','v.idventa','=','dv.idventa')
                ->join('articulo as a','dv.idarticulo','=','a.idarticulo')
                ->select('a.nombre','a.idarticulo','dv.cantidad','dv.precio_venta','dv.descuento')
                ->where('v.tipo_comprobante','=',$tipo_comprobante)
                ->where('v.serie_comprobante','LIKE',$serie)
                ->where('v.num_comprobante','LIKE',$num_comprobante)
                ->orderBy('v.idventa','desc')
                ->get();
        return response()->json([
            'detalle' => $ventas,
        ]);
    }

    public function pdf(){
        $invoice = new Core\Invoice();
        $invoice->pdfPrueba();
    }

    public function enviar(Request $request){
        $fecha = $_POST['txtFECHA_DOCUMENTO'];
        $boletas = DB::table('venta')
                ->select('*')
                ->where('tipo_comprobante','=','03')
                ->where('response_code','=',null)
                ->orderBy('idventa','desc')
                ->get();

        $summaryDocument = new Core\SummaryDocumentsCore();
        // $summaryDocument->buildSummaryDocumentXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta, $empresa->ruc);
        $summaryDocument->buildSummaryDocumentXml('$idcliente','$tipo_comprobante','$serie_comprobante','$num_comprobante','$total_venta','$leyenda','$fecha','$hora','$idarticulo','$cantidad','$precio_venta', '$empresa->ruc');

        // $factura = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
        $factura = "20480072872-RC-20171218-900";
        $summaryDocument->enviarFactura($factura);
        $path = public_path('cdn/cdr\R-'.$factura.'.ZIP');
        LOG::info($path);
        $responseCdr = $summaryDocument->readCdr('',$path,$tipo_comprobante);

        // $v = Venta::findOrFail($venta->idventa);
        // $v->response_code=$responseCdr['code'];
        // $v->descripcion_code=$responseCdr['message'];
        // $v->update();
        
    }
    
}