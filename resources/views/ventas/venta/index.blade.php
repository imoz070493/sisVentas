@extends ('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Ventas <a href="venta/create"><button class="btn btn-success">Nuevo</button></a> <a href="/export-users-excel"><button class="btn btn-success">XLS</button></a> <a href="/export-users-pdf"><button class="btn btn-danger">PDF</button></a> </h3>
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
		@include('ventas.venta.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Comprobante</th>
					<th>Impuesto</th>
					<th>Total</th>
					<th>Estado</th>
					<th>Opciones</th>
				</thead>
				@foreach($ventas as $ven)
				<tr>
					<td>{{$ven->fecha_hora}}</td>
					<td>{{$ven->nombre}}</td>
					<td>{{$ven->tipo_comprobante.': '.$ven->serie_comprobante.'-'.$ven->num_comprobante}}</td>
					<td>{{$ven->impuesto}}</td>
					<td>{{$ven->total_venta}}</td>
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
					<td>
						<a href="{{URL::action('VentaController@show',$ven->idventa)}}">
							<button class="btn btn-primary">Detalles</button>
						</a>
						<a href="{{ asset('cdn/pdf/'.$ruc.'-'.$ven->tipo_comprobante.'-'.$ven->serie_comprobante.'-'.$ven->num_comprobante.'.pdf') }}" target="_blank"><button class="btn btn-danger">PDF</button></a>
						@if($ven->estado=='2')
							<a href="" data-target="#modal-delete-{{$ven->idventa}}" data-toggle="modal"><button class="btn btn-danger">Anular</button></a>
						@endif
						@if($ven->estado=='A')
							<?php $dataCom = $ven->idventa."*".$ven->serie_comprobante."*".$ven->num_comprobante."*".$ven->tipo_comprobante; ?>
							
							<input type="hidden" id="_token" name="_token" value="{{ csrf_token()}}"></input>
							<button class="btn btn-primary" id="reenviar" value="<?php echo $dataCom; ?>"><i id="animacion<?php echo $ven->idventa; ?>"></i>  Reenviar</button>
						@endif
						
					</td>
				</tr>
				@include('ventas.venta.modal')
				@endforeach
			</table>
		</div>
	</div>
</div>
@push('scripts')
<script>

	$(document).on("click", "#probarBoton", function(){
		$('#animacion').html('<img src="{{ asset('img/loader-small.gif') }}"/>');
	});

	$(document).ready(function() { 
		$(document).on("click", "#reenviar", function(){
	    	token = $("#_token").val();
	        comprobante = $(this).val();
	        infoCom = comprobante.split("*");
	        console.log(infoCom)

	       	$("#animacion"+infoCom[0]).html('<img src="{{ asset('img/loader-small.gif') }}"/>');
	       	
	       	
	        $.ajax({
	        	url: "{{ asset('venta/reenviar') }}",
	        	type: "post",
	        	headers: {'X-CSRF-TOKEN': token},
	        	data: {'idVenta': infoCom[0], 'serie':infoCom[1], 'num_comprobante':infoCom[2], 'tipo_comprobante': infoCom[3], "token": token},
	        	success: function(datos){
	        		// $("#serie_comprobante").val(datos['serie']);
	        		// if(!datos){
	        		// 	alert(datos['msg']);
	        		// }
	        		console.log(datos['msg']);
	        		$("#animacion"+infoCom[0]).fadeIn(1000);
	        		// $("#animacion"+infoCom[0]).html('');
	        		// alert(datos['msg'])
	        		location.reload();
	        	},
	        	error: function (data) {
	        		console.log(data);
	        	}
	        });

	    });	
	});

	

</script>
@endpush
@endsection