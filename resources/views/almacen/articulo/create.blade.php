@extends ('layouts.admin')
@section('modulo')
	Almacén
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Almacen</a></li>
    <li class="">Articulos</li>
    <li class="Active">Nuevo</li>
@endsection
@section('submodulo')
	Articulos
@endsection
@section('contenido')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Articulo Nuevo</h3>
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

			{!!Form::open(array('url'=>'almacen/articulo','method'=>'POST','autocomplete'=>'off','files'=>'true'))!!}
			{{Form::token()}}	
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" required value="{{old('nombre')}}" class="form-control" placeholder="Nombre...">
			</div>
		</div>
		<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
			<div class="form-group">
				<label>Categoria</label>
				<select name="idcategoria" class="form-control">
					@foreach($categorias as $cat)
						<option value="{{$cat->idcategoria}}">{{$cat->nombre}}</option>
					@endforeach
				</select>
			</div>
		</div>
		<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
			<div class="form-group">
				<label>Marca</label>
				<select name="idmarca" class="form-control">
					@foreach($marcas as $mar)
						<option value="{{$mar->idmarca}}">{{$mar->nombre}}</option>
					@endforeach
				</select>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Codigo</label>
				<input type="text" name="codigo" required value="{{old('codigo')}}" class="form-control" placeholder="Codigo del Articulo...">
			</div>
		</div>
		<!-- <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
			<div class="form-group">
				<label for="nombre">Stock</label>
				<input type="text" name="stock" required value="{{old('stock')}}" class="form-control" placeholder="Stock...">
			</div>
		</div> -->
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label>Und. Medida</label>
				<select name="idunidad" class="form-control">
					@foreach($unidades as $und)
						@if($und->idunidad_medida=='19')
							<option value="{{$und->idunidad_medida}}" selected>{{$und->titulo}}</option>
						@else
							<option value="{{$und->idunidad_medida}}">{{$und->titulo}}</option>
						@endif
					@endforeach
				</select>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Descripcion</label>
				<input type="text" name="descripcion" value="{{old('descripcion')}}" class="form-control" placeholder="Descripcion del Articulo...">
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="nombre">Imagen</label>
				<input type="file" name="imagen" class="form-control">
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