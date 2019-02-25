@extends ('layouts.admin')
@section('modulo')
	Reportes
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Reportes</a></li>
    <li class="">Ventas</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Ventas
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<h3>Consulta de Ventas por Fecha <a href="/export-users-pdf"><button class="btn btn-danger">PDF</button></a> </h3>
		@if(\Session::has('msg'))
			<div class="alert alert-danger alert-dismissible fade in">
			  <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>
			  <strong>{{\Session::get('msg')}}</strong>
			  {{\Session::forget('msg')}}
			</div>
		@endif
		@if(\Session::has('msB'))
			<div class="alert alert-success alert-dismissible fade in">
			  <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>
			  <strong>{{\Session::get('msg')}}</strong>
			  {{\Session::forget('msg')}}
			</div>
		@endif
		@include('reportes.searchventas')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="reporteCompras" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Comprobante</th>
					<th>Numero</th>
					<th>Total</th>
					<th>Impuesto</th>
					<th>Estado</th>
				</thead>
				@foreach($ventas as $ven)
				<tr>
					<td>{{$ven->fecha_hora}}</td>
					<td>{{$ven->nombre}}</td>
					<td>
						@if($ven->tipo_comprobante=='01')
							Factura
						@elseif($ven->tipo_comprobante=='03')
							Boleta
						@endif
					</td>
					<td>{{$ven->serie_comprobante.'-'.$ven->num_comprobante}}</td>
					<td>{{$ven->total_venta}}</td>
					<td>{{number_format($ven->total_venta-($ven->total_venta/1.18),2)}}</td>
					<td>
						@if($ven->estado=='2')
							<a class="btn btn-success btn-xs">Aceptado</a>
						@elseif($ven->estado=='4')
							<a class="btn btn-default btn-xs">P. An.</a>
						@elseif($ven->estado=='6')
							<a class="btn btn-success btn-xs">An. Ace.</a>
						@elseif($ven->estado=='0' || $ven->estado=='A')
							<a class="btn btn-default btn-xs">Pend. Envio</a>
						@else
							<a class="btn btn-danger btn-xs">Rechazado</a>
						@endif
					</td>
				</tr>
				@endforeach
			</table>
		</div>
	</div>
</div>
@push('scripts')
<script>

	$(document).ready(function() { 
		$(document).on("click", "#exportarExcelVentas", function(){
	    	token = $("#_token").val();
	        console.log(token)

	        if($("#fecha_inicio").val()=='' || $("#fecha_fin").val()==''){
	        	alert("Debe ingresar las fechas")
	        }else{
	        	location.href = "{{ asset('reporte/xls-ventas') }}"+"?fi="+$("#fecha_inicio").val()+"&ff="+$("#fecha_fin").val()
	        }

	    });	
	});

	

</script>
@endpush
@endsection