@extends ('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Listado de Tablas <a href="tabla1/create"><button class="btn btn-success">Nuevo</button></a> </h3>
		@include('seguridad.tabla1.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Column1</th>
					<th>Column2</th>
					<th>Opciones</th>
				</thead>
				@foreach($tablas as $tab)
				<tr>
					<td>{{$tab->idtabla1}}</td>
					<td>{{$tab->column1}}</td>
					<td>{{$tab->column2}}</td>
					<td>
						<a href="{{URL::action('Tabla1Controller@edit',$tab->idtabla1)}}">
							<button class="btn btn-info">Editar</button>
						</a>
						<a href="" data-target="#modal-delete-{{$tab->idtabla1}}" data-toggle="modal"><button class="btn btn-danger">Eliminar</button></a>
					</td>
				</tr>
				@include('seguridad.tabla1.modal')
				@endforeach
			</table>
		</div>
		{{$tablas->render()}}
	</div>
</div>
@endsection