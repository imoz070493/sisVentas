@extends ('layouts.admin')
@section('modulo')
	Almacén
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Almacen</a></li>
    <li class="">Categoría</li>
    <li class="Active">Editar</li>
@endsection
@section('submodulo')
	Categorias
@endsection
@section('contenido')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Editar Categoria: {{$categoria->nombre}}</h3>
			@if(count($errors)>0)
			<div class="alert alert-danger">
				<ul>
				@foreach ($errors->all() as $error )
					<li>{{$error}}</li>
				</ul>
				@endforeach
			</div>
			@endif

			{!!Form::model($categoria,['method'=>'PATCH','route'=>['almacen.categoria.update',$categoria->idcategoria]])!!}
			{{Form::token()}}	
			<div class="form-group">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" class="form-control" value="{{$categoria->nombre}}" placeholder="Nombre...">
			</div>
			<div class="form-group">
				<label for="descripcion">Descripcion</label>
				<input type="text" name="descripcion" class="form-control" value="{{$categoria->descripcion}}" placeholder="Descripcion...">
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<button class="btn btn-info" type="reset">Limpiar</button>
				<a class="btn btn-danger" href="{{ asset('almacen/categoria') }}">Cancelar</a>
			</div>
			{!!Form::close()!!}
		</div>
	</div>
@endsection