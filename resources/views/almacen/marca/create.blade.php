@extends ('layouts.admin')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Nueva Marca</h3>
			@if(count($errors)>0)
			<div class="alert alert-danger">
				<ul>
				@foreach ($errors->all() as $error )
					<li>{{$error}}</li>
				</ul>
				@endforeach
			</div>
			@endif

			{!!Form::open(array('url'=>'almacen/marca','method'=>'POST','autocomplete'=>'off'))!!}
			{{Form::token()}}	
			<div class="form-group">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" class="form-control" placeholder="Nombre...">
			</div>
			<div class="form-group">
				<label for="descripcion">Descripcion</label>
				<input type="text" name="descripcion" class="form-control" placeholder="Descripcion...">
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<button class="btn btn-info" type="reset">Limpiar</button>
				<a class="btn btn-danger" href="{{ asset('almacen/marca') }}">Cancelar</a>
			</div>
			{!!Form::close()!!}
		</div>
	</div>
@endsection