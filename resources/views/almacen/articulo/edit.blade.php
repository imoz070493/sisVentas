@extends ('layouts.admin')
@section('modulo')
	Almacén
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Almacen</a></li>
    <li class="">Articulos</li>
    <li class="Active">Editar</li>
@endsection
@section('submodulo')
	Articulos
@endsection
@section('contenido')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Editar Articulo: {{$articulo->nombre}}</h3>
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
			{!!Form::model($articulo,['method'=>'PATCH','route'=>['almacen.articulo.update',$articulo->idarticulo],'files'=>'true'])!!}
			{{Form::token()}}	
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" required value="{{$articulo->nombre}}" class="form-control">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label>Categoria</label>
				<select name="idcategoria" class="form-control">
					@foreach($categorias as $cat)
						@if($cat->idcategoria == $articulo->idcategoria)
						<option value="{{$cat->idcategoria}}" selected>{{$cat->nombre}}</option>
						@else
						<option value="{{$cat->idcategoria}}">{{$cat->nombre}}</option>
						@endif
					@endforeach
				</select>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Codigo</label>
				<input type="text" name="codigo" required value="{{$articulo->codigo}}" class="form-control">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Stock</label>
				<input type="text" name="stock" required value="{{$articulo->stock}}" class="form-control">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Descripcion</label>
				<input type="text" name="descripcion" value="{{$articulo->descripcion}}" class="form-control">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Imagen</label>
				<input type="file" name="imagen" class="form-control">
				@if(($articulo->imagen)!="")
				<img src="{{asset('imagenes/articulos/'.$articulo->imagen)}}" height="300px" width="300px">
				@endif
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Guardar</button>
				<button class="btn btn-info" type="reset">Limpiar</button>
				<a class="btn btn-danger" href="{{ asset('almacen/articulo') }}">Cancelar</a>
			</div>
		</div>
	</div>
			{!!Form::close()!!}
		
@endsection