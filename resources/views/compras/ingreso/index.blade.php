@extends ('layouts.admin')
@section('modulo')
	Compras
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Compras</a></li>
    <li class="">Ingresos</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Ingresos
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Ingresos <a href="ingreso/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('compras.ingreso.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Fecha</th>
					<th>Proveedor</th>
					<th>Comprobante</th>
					<th>Impuesto</th>
					<th>Total</th>
					<th>Estado</th>
					<th>Opciones</th>
				</thead>
				@foreach($ingresos as $ing)
				<tr>
					<td>{{$ing->fecha_hora}}</td>
					<td>{{$ing->nombre}}</td>
					<td>{{$ing->tipo_comprobante.': '.$ing->serie_comprobante.'-'.$ing->num_comprobante}}</td>
					<td>{{$ing->impuesto}}</td>
					<td>{{$ing->total}}</td>
					<td>{{$ing->estado}}</td>
					<td>
						<a href="{{URL::action('IngresoController@show',$ing->idingreso)}}">
							<button class="btn btn-primary" title="Detalles"><i class="fa fa-list"></i></button>
						</a>
						<a href="" data-target="#modal-delete-{{$ing->idingreso}}" data-toggle="modal"><button class="btn btn-danger" title="Anular"><i class="fa fa-close"></i></button></a>
					</td>
				</tr>
				@include('compras.ingreso.modal')
				@endforeach
			</table>
		</div>
		{{$ingresos->render()}}
	</div>
</div>
@endsection