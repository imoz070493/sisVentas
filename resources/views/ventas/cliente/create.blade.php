@extends ('layouts.admin')
@section('modulo')
	Ventas
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Ventas</a></li>
    <li class="">Clientes</li>
    <li class="Active">Nuevo</li>
@endsection
@section('submodulo')
	Clientes
@endsection
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Nuevo Cliente</h3>
			@if(count($errors)>0)
			<div class="alert alert-danger">
				<ul>
				@foreach ($errors->all() as $error )
					<li>{{$error}}</li>
				</ul>
				@endforeach
			</div>
			@endif
		</div>
	</div>

			{!!Form::open(array('url'=>'ventas/cliente','method'=>'POST','autocomplete'=>'off'))!!}
			{{Form::token()}}	
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label>Documento</label>
				<select name="tipo_documento" id="tipo_documento" class="form-control">
					<option value="01">DNI</option>
					<option value="02">RUC</option>
				</select>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="num_documento">Número documento</label>
				<div class="row"> 
					<div class="col-lg-10 col-md-10 col-sm-10 col-xs-12">
						<input type="hidden" id="num_documento_cliente" name="num_documento_cliente">
						<input type="text" id="num_documento" name="num_documento" value="{{old('num_documento')}}" class="form-control" placeholder="Numero Documento...">
					</div>
					<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
						<button type="button" class="btn btn-default btn-buscar form-control"><i class="fa fa-search"></i></button>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Nombre o Razon Social</label>
				<input type="text" id="nombre" name="nombre" required value="{{old('nombre')}}" class="form-control" placeholder="Nombre  del Cliente o Razon Social">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="direccion">Direccion</label>
				<input type="text" id="direccion" name="direccion" value="{{old('direccion')}}" class="form-control" placeholder="Direccion...">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="telefono">Telefono</label>
				<input type="text" id="telefono" name="telefono" value="{{old('telefono')}}" class="form-control" placeholder="Telefono...">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="email">Email</label>
				<input type="email" id="email" name="email" value="{{old('email')}}" class="form-control" placeholder="Email...">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<button class="btn btn-info" type="reset">Limpiar</button>
				@if(\Session::has('venta') && \Session::has('venta')==01)
					<a class="btn btn-danger" href="{{ asset('ventas/venta/create') }}">Cancelar</a>
				@else
					<a class="btn btn-danger" href="{{ asset('ventas/cliente') }}">Cancelar</a>
				@endif
			</div>
		</div>
	</div>
			
			{!!Form::close()!!}

@push('scripts')
<script type="text/javascript">
	
	$(".btn-buscar").click(function(){
		// api = 'http://'+window.location.host+'/reniec/obtener_dni.php'

		$("#nombre").val("");
		$("#direccion").val("");
		tipo_documento = $("#tipo_documento").val();

		if(tipo_documento=='01'){
			api = 'http://'+window.location.host+'/reniec/obtener_dni.php'
		}
		if(tipo_documento=='02'){
			api = 'http://'+window.location.host+'/reniec/obtener_ruc.php'
		}


		num_documento = $("#num_documento").val();
		// api = 'http://localhost:8080/reniec/obtener_dni.php'
		console.log(api)
		$.ajax({
			url: api,
			type: "POST",
			// headers: {'X-CSRF-TOKEN': ''},
			data: {'num_documento': num_documento},
			success: function(datos){
				// console.log(datos)
				// // console.log(JSON.parse(datos))
				// console.log(tipo_documento)

				try {
				  	respuesta = JSON.parse(datos)
					if(tipo_documento=="01"){
						$("#nombre").val(respuesta.nombres+" "+respuesta.apellidoPaterno+" "+respuesta.apellidoMaterno);
					}
					if(tipo_documento=="02"){
						$("#nombre").val(respuesta.razonSocial);
						$("#direccion").val(respuesta.direccion);

					}
				}
				catch(err) {
					alert(datos)
					console.log(datos)
				}
				// 
			},
			error: function (data) {
				alert("Ingrese correctamente la informacion")
				console.log(JSON.parse(datos));
			}
		});
	});

</script>
@endpush
		
@endsection