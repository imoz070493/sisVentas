@extends ('layouts.admin')
@section('modulo')
	Ventas
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Ventas</a></li>
    <li class="">Notas</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Notas de Credito / Debito
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Lista de Notas Credito/Debito <a href="notas/create"><button class="btn btn-success">Nuevo</button></a> </h3>
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
					<td>@if($ven->response_code=='0')
								<a class="btn btn-success btn-xs">&nbsp;&nbsp;Aceptado&nbsp;&nbsp;</a>	
							@else
								<a class="btn btn-danger btn-xs">Rechazado</a>
							@endif</td>
					<td>
						<a href="{{URL::action('NotasController@show',$ven->idventa)}}">
							<button class="btn btn-primary" title="Detalles"><i class="fa fa-list"></i></button>
						</a>
						<!-- <a href="" data-target="#modal-delete-{{$ven->idventa}}" data-toggle="modal"><button class="btn btn-danger">Anular</button></a> -->
						<a href="{{ asset('cdn/pdf/'.$ruc.'-'.$ven->tipo_comprobante.'-'.$ven->serie_comprobante.'-'.$ven->num_comprobante.'.pdf') }}" target="_blank"><button class="btn btn-danger" title="PDF"><i class="fa fa-file-pdf-o"></i></button></a>

					</td>
				</tr>
				@include('ventas.notas.modal')
				@endforeach
			</table>
		</div>
		{{--$ventas->render()--}}
	</div>
</div>
@endsection