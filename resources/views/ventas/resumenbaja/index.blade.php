@extends ('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Resumen de Bajas <a href="notas/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('ventas.resumenbaja.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>ID</th>
					<th>Codigo Hash</th>
					<th>N° Ticket</th>
					<th>Estado</th>
					<th>Fecha Doc</th>
					<th>Fech Env</th>
					<th>Opciones</th>
				</thead>
				@foreach($resumen as $res)
				<tr>
					<td>{{$res->idresumen}}</td>
					<td>{{$res->hash}}</td>
					<td>{{$res->ticket}}</td>
					<td>{{$res->estado}}</td>
					<td>{{$res->fecha_documento}}</td>
					<td>{{$res->fecha}}</td>
					<td>
						<button class="btn btn-danger" type="button"><i class="fa fa-check"></i></button>
					</td>
				</tr>
				@include('ventas.venta.modal')
				@endforeach
			</table>
		</div>
	</div>
</div>
@endsection