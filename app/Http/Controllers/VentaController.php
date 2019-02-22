<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use sisVentas\Http\Requests\VentaFormRequest;
use sisVentas\Venta;
use sisVentas\Permiso;
use sisVentas\DetalleVenta;
use DB;

use sisVentas\Http\Controllers\Core;
use sisVentas\Http\Controllers\Util;

use Carbon\Carbon;
use Response;
use Illuminate\Support\Collection;

class VentaController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth');
        $this->middleware('permisoVentas');
    }

    public function index(Request $request)
    {

        if($request)
    	{
    		$query = trim($request->get('searchText'));
    		$ventas = DB::table('venta as v')
    			->join('persona as p','v.idcliente','=','p.idpersona')
    			->join('detalle_venta as dv','v.idventa','=','dv.idventa')
    			->select('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.impuesto','v.estado','v.total_venta','v.response_code')
    			->where('v.num_comprobante','LIKE','%'.$query.'%')
                ->where('v.tipo_comprobante','=','01')
                ->orWhere('v.tipo_comprobante','=','03')
    			->orderBy('v.idventa','des')
    			->groupBy('v.idventa','v.fecha_hora','p.nombre','v.tipo_comprobante','v.serie_comprobante','v.impuesto','v.estado')
    			->get();

            LOG::info("---------------------------------");
            LOG::info("RESPUESTA DESDE EL METODO INDEX");
            LOG::info("---------------------------------");
            // dd($ventas);

            $empresa = DB::table('config')
            ->where('estado','=','1')
            ->first();

            $permiso = DB::table('permiso')
                ->where('idrol','=',\Auth::user()->idrol)
                ->orderBy('idrol','desc')
                ->get();

            $request->session()->put('permiso',$permiso);
            $request->session()->put('nombre_comercial',$empresa->nombre_comercial);

    		return view('ventas.venta.index',["ventas"=>$ventas,"searchText"=>$query, "ruc" => $empresa->ruc]);
    	}

    }

    public function create()
    {
    	$personas = DB::table('persona')->where('tipo_persona','=','Cliente')->get();
    	$articulos = DB::table('articulo as art')
    		->join('detalle_ingreso as di','art.idarticulo','=','di.idarticulo')
    		->select(DB::raw('CONCAT(art.codigo," ",art.nombre) AS articulo'),'art.idarticulo','art.stock',DB::raw('avg(di.precio_venta) as precio_promedio'))
    		->where('art.estado','=','Activo')
    		->where('art.stock','>','0')
    		->groupBy('articulo','art.idarticulo','art.stock')
    		->get();
    	return view("ventas.venta.create",["personas"=>$personas,'articulos'=>$articulos]);
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

    			// $mytime = Carbon::now('America/Lima');
    			// $venta->fecha_hora = $mytime->toDateTimeString();
                
                $time = strtotime($request->get('fecha').date("H:i:s"));
                $fecha = date('Y-m-d H:i:s',$time);
                
                $venta->fecha_hora = $fecha;
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

        $empresa = DB::table('config')
                ->where('estado','=','1')
                ->first();

        $invoice = new Core\Invoice();

        $util = new Util\UtilHelper();

        $total_venta = $request->get('total_venta');
        $leyenda = $util->numtoletras($total_venta);

        $idcliente = $request->get('idcliente');
        $tipo_comprobante = $request->get('tipo_comprobante');
        $serie_comprobante = $request->get('serie_comprobante');
        $num_comprobante = $request->get('num_comprobante');

        $factura = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;

        $mytime = Carbon::now('America/Lima');
        $fecha = $mytime->toDateString();
        $hora = $mytime->toTimeString();

        $idarticulo = $request->get('idarticulo');
        $cantidad = $request->get('cantidad');
        $descuento = $request->get('descuento');
        $precio_venta = $request->get('precio_venta');

        if($request->get('tipo_comprobante')=='01'){
            
            $invoice->buildInvoiceXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta, $empresa);
        }

        if($request->get('tipo_comprobante')=='03'){

            $invoice->buildInvoiceXmlB($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta, $empresa);

            $v = Venta::findOrFail($venta->idventa);
            $v->estado = '0';
            $v->update();
        }

        

        $cliente = DB::table('persona as per')
            ->join('venta as v','per.idpersona','=','v.idcliente')
            ->select('per.nombre','per.direccion','per.num_documento','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.fecha_hora')
            ->where('v.idventa','=',$venta->idventa)
            ->first();

        $items = DB::table('venta as v')
            ->join('detalle_venta as dv','dv.idventa','=','v.idventa')
            ->join('articulo as art','art.idarticulo','=','dv.idarticulo')
            ->select('art.codigo','art.nombre','dv.cantidad','dv.precio_venta',DB::raw('dv.cantidad * dv.precio_venta AS total'))
            ->where('v.idventa','=',$venta->idventa)
            ->get();
        
        $response = $invoice->readSignDocument(public_path().'\cdn\document\prueba21\\'.$factura.'.ZIP');

        if($request->get('tipo_comprobante')=='01'){
            $invoice->crearPDF($empresa,$cliente,$items, $leyenda,$response['sign']);
        }
        if($request->get('tipo_comprobante')=='03'){
            $invoice->crearPDFA7($empresa,$cliente,$items, $leyenda,$response['sign']);
        }

        if($request->get('tipo_comprobante')=='01'){

            $invoice->enviarFactura($factura);
            // $respuesta = $invoice->enviarFactura($factura);
            // // dd($respuesta);
            if(\Session::get('fallo')){
                \Session::put('msg','FALLO LA CONEXION CON LA SUNAT');
                return Redirect::to('ventas/venta');
            }
            
            $path = public_path('cdn/cdr\R-'.$factura.'.ZIP');
            LOG::info($path);
            $responseCdr = $invoice->readCdr('',$path,$tipo_comprobante);

            if($responseCdr['code']=='0'){
                $estado = '2';
            }else{
                $estado = '1';
            }

            $v = Venta::findOrFail($venta->idventa);
            $v->response_code=$responseCdr['code'];
            $v->descripcion_code=$responseCdr['message'];
            $v->estado = $estado;
            $v->update();
        }

        
        


    	return Redirect::to('ventas/venta');
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
    	$venta->Estado='4';
    	$venta->update();
    	return Redirect::to('ventas/venta');
    }

    public function peticion(Request $request){
        $tipo_comprobante = $_POST['tipoComprobante'];
        if($tipo_comprobante=='01'){
            $serie = "F001";    
        }
        if($tipo_comprobante=='03'){
            $serie = "B001";    
        }        

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

    //METODOS DE PRUEBA

    public function pdf(){
        $invoice = new Core\Invoice();
        $invoice->pdfPrueba();
    }

    public function leerFirma(){
        $invoice = new Core\Invoice();
        // $invoice->readSignDocument('C:\xampp1\htdocs\sisVentas\public\cdn\cdr\R-20100066603-01-F001-00000017.ZIP');
        $response = $invoice->readSignDocument('C:\xampp1\htdocs\sisVentas\public\cdn\document\prueba21\20100066603-01-F001-00000017.ZIP');
        LOG::info('Desde leerFirma'.$response['sign']);
    }

    public function crearPdfA7(){
        $items = DB::table('venta as v')
            ->join('detalle_venta as dv','dv.idventa','=','v.idventa')
            ->join('articulo as art','art.idarticulo','=','dv.idarticulo')
            ->select('art.codigo','art.nombre','dv.cantidad','dv.precio_venta',DB::raw('dv.cantidad * dv.precio_venta AS total'))
            ->where('v.idventa','=',236)
            ->get();
        $empresa = DB::table('config')
                ->where('estado','=','1')
                ->first();
        $cliente = DB::table('persona as per')
            ->join('venta as v','per.idpersona','=','v.idcliente')
            ->select('per.nombre','per.direccion','per.num_documento','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.fecha_hora')
            ->where('v.idventa','=',236)
            ->first();
        $invoice = new Core\Invoice();
        $invoice->crearPDFA7($empresa,$cliente,$items);
    }

    public function reenviar(Request $request){
        $idVenta = $_POST['idVenta'];
        $serie_comprobante = $_POST['serie'];
        $num_comprobante = $_POST['num_comprobante'];
        $tipo_comprobante = $_POST['tipo_comprobante'];



        $empresa = DB::table('config')
                ->where('estado','=','1')
                ->first();

        $factura = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
        $invoice = new Core\Invoice();
        $invoice->enviarFactura($factura);
        // $respuesta = $invoice->enviarFactura($factura);
        // // dd($respuesta);
        if(\Session::get('fallo')){
            \Session::put('msg','FALLO LA CONEXION CON LA SUNAT');
            // return Redirect::to('ventas/venta');
            return response()->json([
                'msg' => "FALLO LA CONEXION CON LA SUNAT",
            ]);
        }
        
        $path = public_path('cdn/cdr\R-'.$factura.'.ZIP');
        LOG::info($path);
        $responseCdr = $invoice->readCdr('',$path,$tipo_comprobante);

        if($responseCdr['code']=='0'){
            $estado = '2';
        }else{
            $estado = '1';
        }

        $v = Venta::findOrFail($idVenta);
        $v->response_code=$responseCdr['code'];
        $v->descripcion_code=$responseCdr['message'];
        $v->estado = $estado;
        $v->update();

        // $cliente = DB::table('persona as per')
        //     ->join('venta as v','per.idpersona','=','v.idcliente')
        //     ->select('per.nombre','per.direccion','per.num_documento','v.tipo_comprobante','v.serie_comprobante','v.num_comprobante','v.fecha_hora')
        //     ->where('v.idventa','=',$idVenta)
        //     ->first();

        // $items = DB::table('venta as v')
        //     ->join('detalle_venta as dv','dv.idventa','=','v.idventa')
        //     ->join('articulo as art','art.idarticulo','=','dv.idarticulo')
        //     ->select('art.codigo','art.nombre','dv.cantidad','dv.precio_venta',DB::raw('dv.cantidad * dv.precio_venta AS total'))
        //     ->where('v.idventa','=',$idVenta)
        //     ->get();
        
        // $response = $invoice->readSignDocument(public_path().'\cdn\document\prueba21\\'.$factura.'.ZIP');

        // if($request->get('tipo_comprobante')=='01'){
        //     $invoice->crearPDF($empresa,$cliente,$items, $leyenda,$response['sign']);
        // }
        // if($request->get('tipo_comprobante')=='03'){
        //     $invoice->crearPDFA7($empresa,$cliente,$items, $leyenda,$response['sign']);
        // }
        
        // return response()->json([
        //     'msg' => "LA FACTURA SE ENVIO CORRECTAMENTE",
        //     // 'correlativo' => str_pad($num_comprobante + 1,  8, "0", STR_PAD_LEFT),
        //     // 'ventas'=>$ventas
        // ]);   


        \Session::put('msgB','ENVIO CORRECTO');
        // return Redirect::to('ventas/venta');
            return response()->json([
            'msg' => "SE ENVIO CORRECTAMENTE LA FACTURA",
            // 'correlativo' => str_pad($num_comprobante + 1,  8, "0", STR_PAD_LEFT),
            // 'ventas'=>$ventas
            ]);
        

        // return Redirect::to('ventas/venta');
    }
}
