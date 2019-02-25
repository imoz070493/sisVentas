@extends ('layouts.admin')
@section('modulo')
	Acceso
@endsection
@section('ruta')
	<li><a href="#"><i class="fa fa-dashboard"></i> Acceso</a></li>
    <li class="">Usuarios</li>
    <li class="Active">Listado</li>
@endsection
@section('submodulo')
	Usuarios
@endsection
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Usuarios <a href="usuario/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('seguridad.usuario.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Nombre</th>
					<th>Email</th>
					<th>Opciones</th>
				</thead>
				@foreach($usuarios as $usu)
				<tr>
					<td>{{$usu->id}}</td>
					<td>{{$usu->name}}</td>
					<td>{{$usu->email}}</td>
					<td>
						<a href="{{URL::action('UsuarioController@edit',$usu->id)}}">
							<button class="btn btn-info" title="Editar"><i class="fa fa-edit"></i></button>
						</a>
						<a href="" data-target="#modal-delete-{{$usu->id}}" data-toggle="modal"><button class="btn btn-danger" title="Eliminar"><i class="fa fa-close"></i></button></a>
					</td>
				</tr>
				@include('seguridad.usuario.modal')
				@endforeach
			</table>
		</div>
		{{$usuarios->render()}}
	</div>
</div>
@endsection