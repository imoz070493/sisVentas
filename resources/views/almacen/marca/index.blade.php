@extends ('layouts.admin')
@section('modulo')
	Almacén
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Almacen</a></li>
    <li class="">Marca</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Marca
@endsection
@section('contenido')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Marcas <a href="marca/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('almacen.marca.search')
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
				@foreach($marcas as $mar)
				<tr>
					<td>{{$mar->idmarca}}</td>
					<td>{{$mar->nombre}}</td>
					<td>{{$mar->descripcion}}</td>
					<td>
						<a href="{{URL::action('MarcaController@edit',$mar->idmarca)}}">
							<button class="btn btn-info" title="Editar"><i class="fa fa-edit"></i></button>
						</a>
						<a href="" data-target="#modal-delete-{{$mar->idmarca}}" data-toggle="modal"><button class="btn btn-danger" title="Eliminar"><i class="fa fa-close"></i></button></a>
					</td>
				</tr>
				@include('almacen.marca.modal')
				@endforeach
			</table>
		</div>
		{{$marcas->render()}}
	</div>
</div>
@endsection