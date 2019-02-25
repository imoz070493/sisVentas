@extends ('layouts.admin')
@section('modulo')
	Compras
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Compras</a></li>
    <li class="">Ingresos</li>
    <li class="Active">Detalles</li>
@endsection
@section('submodulo')
	Ingresos
@endsection
@section('contenido')
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="proveedor">Proveedor</label>
				<p>{{$ingreso->nombre}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Documento</label>
				<p>{{$ingreso->tipo_comprobante}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="serie_comprobante">Serie Comprobante</label>
				<p>{{$ingreso->serie_comprobante}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="num_comprobante">Numero Comprobante</label>
				<p>{{$ingreso->num_comprobante}}</p>
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
							<th>Precio Compra</th>
							<th>Precio Venta</th>
							<th>Subtotal</th>
						</thead>
						<tbody>
							@foreach($detalles as $det)
							<tr>
								<td>{{$det->articulo}}</td>
								<td>{{$det->cantidad}}</td>
								<td>{{$det->precio_compra}}</td>
								<td>{{$det->precio_venta}}</td>
								<td>{{$det->cantidad*$det->precio_compra}}</td>
							</tr>
							@endforeach
						</tbody>
						<tfood>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th><h4 id="total">S/. {{$ingreso->total}}</h4></th>
						</tfood>
						
					</table>
					<a class="btn btn-info" href="{{ asset('compras/ingreso') }}">Regresar</a>
				</div>
			</div>
		</div>

		
	</div>

@endsection