@extends ('layouts.admin')
@section('modulo')
	Ventas
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Ventas</a></li>
    <li class="">Venta</li>
    <li class="Active">Detalles</li>
@endsection
@section('submodulo')
	Ventas
@endsection
@section('contenido')
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="proveedor">Cliente</label>
				<p>{{$venta->nombre}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Documento</label>
				<p>
					@if($venta->tipo_comprobante=='01')
						FACTURA ELECTRÓNICA
					@elseif($venta->tipo_comprobante=='03')
						BOLETA ELECTRÓNICA
					@endif
				</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="serie_comprobante">Serie Comprobante</label>
				<p>{{$venta->serie_comprobante}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="num_comprobante">Numero Comprobante</label>
				<p>{{$venta->num_comprobante}}</p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="panel panel-primary">
			<div class="panel-body">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<table id="detalles" class="table table-striped table-bordered table-condensed table-hover">
						<thead style="background-color:#A9D0F5">
							<th>Articulo</th>
							<th>Cantidad</th>
							<th>Precio Venta</th>
							<th>Descuento</th>
							<th>Subtotal</th>
						</thead>
						<tbody>
							@foreach($detalles as $det)
							<tr>
								<td>{{$det->articulo}}</td>
								<td>{{$det->cantidad}}</td>
								<td>{{$det->precio_venta}}</td>
								<td>{{$det->descuento}}</td>								
								<td>{{number_format($det->cantidad*$det->precio_venta-$det->descuento, 2)}}</td>
							</tr>
							@endforeach
						</tbody>
						<tfood>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th><h4 id="total">S/. {{$venta->total_venta}}</h4></th>
						</tfood>
						
					</table>
					<a class="btn btn-info" href="{{ asset('ventas/venta') }}">Regresar</a>
				</div>
			</div>
		</div>

		
	</div>

@endsection