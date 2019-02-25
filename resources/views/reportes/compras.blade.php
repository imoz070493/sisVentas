@extends ('layouts.admin')
@section('modulo')
	Reportes
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Reportes</a></li>
    <li class="">Compras</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Compras
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<h3>Consulta de Compras por Fecha <a href="/export-users-pdf"><button class="btn btn-danger">PDF</button></a> </h3>
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
		@include('reportes.searchcompras')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="reporteCompras" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Fecha</th>
					<th>Proveedor</th>
					<th>Comprobante</th>
					<th>Numero</th>
					<th>Total</th>
					<th>Impuesto</th>
					<th>Estado</th>
				</thead>
				@foreach($compras as $com)
				<tr>
					<td>{{$com->fecha_hora}}</td>
					<td>{{$com->nombre}}</td>
					<td>{{$com->tipo_comprobante}}</td>
					<td>{{$com->serie_comprobante.'-'.$com->num_comprobante}}</td>
					<td>{{$com->total_compra}}</td>
					<td>{{number_format($com->total_compra-($com->total_compra/1.18),2)}}</td>
					<td>
						@if($com->estado=='A')
							<a class="btn btn-success btn-xs">Aceptado</a>
						@elseif($ven->estado=='C')
							<a class="btn btn-danger btn-xs">Anulado</a>
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
		$(document).on("click", "#exportarExcelCompras", function(){
	    	token = $("#_token").val();
	        console.log(token)

	        if($("#fecha_inicio").val()=='' || $("#fecha_fin").val()==''){
	        	alert("Debe ingresar las fechas")
	        }else{
	        	location.href = "{{ asset('reporte/xls-compras') }}"+"?fi="+$("#fecha_inicio").val()+"&ff="+$("#fecha_fin").val()
	        }

	    });	
	});

	

</script>
@endpush
@endsection