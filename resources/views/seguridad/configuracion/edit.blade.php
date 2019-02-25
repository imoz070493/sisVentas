@extends ('layouts.admin')
@section('modulo')
	Configuracion
@endsection
@section('submodulo')
	Perfil
@endsection
@section('contenido')
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<h3>Perfil BETA/PRODUCCION</h3>
			@if(count($errors)>0)
			<div class="alert alert-danger">
				<ul>
				@foreach ($errors->all() as $error )
					<li>{{$error}}</li>
				</ul>
				@endforeach
			</div>
			@endif

			{!!Form::model($perfil,['method'=>'PATCH','route'=>['seguridad.configuracion.update',$perfil->id]])!!}
			{{Form::token()}}	
			<div class="row">
				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
					<div class="form-group">
						<label for="razon_social">Razon Social</label>
						<input type="text" name="razon_social" class="form-control" value="{{$perfil->razon_social}}">
					</div>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
					<div class="form-group">
						<label for="nombre_comercial">Nombre Comercial</label>
						<input type="text" name="nombre_comercial" class="form-control" value="{{$perfil->nombre_comercial}}">
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label for="ruc">RUC</label>
						<input type="text" name="ruc" class="form-control" value="{{$perfil->ruc}}">
					</div>
				</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
					<div class="form-group">
						<label for="direccion">Direccion</label>
						<input type="text" name="direccion" class="form-control" value="{{$perfil->direccion}}">
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label for="departamento">Departamento</label>
						<input type="text" name="departamento" class="form-control" value="{{$perfil->departamento}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="provincia">Provincia</label>
						<input type="text" name="provincia" class="form-control" value="{{$perfil->provincia}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="distrito">Distrito</label>
						<input type="text" name="distrito" class="form-control" value="{{$perfil->distrito}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="codpais">COD.PAIS</label>
						<input type="text" name="codpais" class="form-control" value="{{$perfil->codpais}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="ubigeo">Ubigeo</label>
						<input type="text" name="ubigeo" class="form-control" value="{{$perfil->ubigeo}}">
					</div>
				</div>

				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label for="telefono">Telefono</label>
						<input type="text" name="telefono" class="form-control" value="{{$perfil->telefono}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="usuario">Usuario SOL</label>
						<input type="text" name="usuario" class="form-control" value="{{$perfil->usuario}}">
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label for="clave">Clave SOL</label>
						<input type="text" name="clave" class="form-control" value="{{$perfil->clave}}">
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label for="firma">Contraseña Cert.</label>
						<input type="text" name="firma" class="form-control" value="{{$perfil->firma}}">
					</div>
				</div>
				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
					<div class="form-group">
						<label for="correo">Correo</label>
						<input type="text" name="correo" class="form-control" value="{{$perfil->correo}}">
					</div>
				</div>

				<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
					<div class="form-group">
						<button class="btn btn-primary" type="submit">Guardar</button>
						<button class="btn btn-danger" type="reset">Cancelar</button>
						<a class="btn btn-danger" href="/seguridad/configuracion">Regresar</a>
					</div>
				</div>
			</div>
			{!!Form::close()!!}
		</div>
	</div>
@endsection