@extends ('layouts.admin')
@section('modulo')
	Almacén
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Almacen</a></li>
    <li class="">Categoría</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Categorías
@endsection
@section('contenido')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Categorias <a href="categoria/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('almacen.categoria.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Nombre</th>
					<th>Descripcion</th>
					<th>Opciones</th>
				</thead>
				@foreach($categorias as $cat)
				<tr>
					<td>{{$cat->idcategoria}}</td>
					<td>{{$cat->nombre}}</td>
					<td>{{$cat->descripcion}}</td>
					<td>
						<a href="{{URL::action('CategoriaController@edit',$cat->idcategoria)}}">
							<button class="btn btn-info" title="Editar"><i class="fa fa-edit"></i></button>
						</a>
						<a href="" data-target="#modal-delete-{{$cat->idcategoria}}" data-toggle="modal"><button class="btn btn-danger" title="Eliminar"><i class="fa fa-close"></i></button></a>
					</td>
				</tr>
				@include('almacen.categoria.modal')
				@endforeach
			</table>
		</div>
		{{$categorias->render()}}
	</div>
</div>
@endsection