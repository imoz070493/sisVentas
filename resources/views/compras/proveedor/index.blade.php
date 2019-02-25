@extends ('layouts.admin')
@section('modulo')
	Compras
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Compras</a></li>
    <li class="">Proveedores</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Proveedores
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Proveedores <a href="proveedor/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('compras.proveedor.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Nombre</th>
					<th>Tipo Doc.</th>
					<th>Número Doc.</th>
					<th>Telefono</th>
					<th>Email</th>
					<th>Opciones</th>
				</thead>
				@foreach($personas as $per)
				<tr>
					<td>{{$per->idpersona}}</td>
					<td>{{$per->nombre}}</td>
					<td>{{$per->tipo_documento}}</td>
					<td>{{$per->num_documento}}</td>
					<td>{{$per->telefono}}</td>
					<td>{{$per->email}}</td>
					<td>
						<a href="{{URL::action('ProveedorController@edit',$per->idpersona)}}">
							<button class="btn btn-info" title="Editar"><i class="fa fa-edit"></i></button>
						</a>
						<a href="" data-target="#modal-delete-{{$per->idpersona}}" data-toggle="modal"><button class="btn btn-danger" title="Eliminar"><i class="fa fa-close"></i></button></a>
					</td>
				</tr>
				@include('compras.proveedor.modal')
				@endforeach
			</table>
		</div>
		{{$personas->render()}}
	</div>
</div>
@endsection