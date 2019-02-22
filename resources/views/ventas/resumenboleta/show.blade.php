@extends ('layouts.admin')
@section('contenido')
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="proveedor">Cliente</label>
				<p>{{$ven->nombre}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Documento</label>
				<p>{{$ven->tipo_comprobante}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="serie_comprobante">Serie Comprobante</label>
				<p>{{$ven->serie_comprobante}}</p>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="num_comprobante">Numero Comprobante</label>
				<p>{{$ven->num_comprobante}}</p>
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
							<th>Precio ven</th>
							<th>Descuento</th>
							<th>Subtotal</th>
						</thead>
						<tbody>
							@foreach($detalles as $det)
							<tr>
								<td>{{$det->articulo}}</td>
								<td>{{$det->cantidad}}</td>
								<td>{{$det->precio_ven}}</td>
								<td>{{$det->descuento}}</td>								
								<td>{{$det->cantidad*$det->precio_ven-$det->descuento}}</td>
							</tr>
							@endforeach
						</tbody>
						<tfood>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th><h4 id="total">S/. {{$ven->total_ven}}</h4></th>
						</tfood>
						
					</table>
				</div>
			</div>
		</div>

		
	</div>

@endsection