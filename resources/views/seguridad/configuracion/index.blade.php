@extends ('layouts.admin')
@section('contenido')
<div class="row">
	<div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
		<h3>Perfil BETA/PRODUCCION</h3>
		@include('seguridad.configuracion.search')
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-condensed table-hover">
				<thead>
					<th>Id</th>
					<th>Razon Social</th>
					<th>RUC</th>
					<th>Tipo</th>
					<th>Estado</th>
					<th>Opciones</th>
				</thead>
				@foreach($perfiles as $per)
				<tr>
					<td>{{$per->id}}</td>
					<td>{{$per->razon_social}}</td>
					<td>{{$per->ruc}}</td>
					<td>
						@if($per->tipo==3)
							BETA
						@else
							PRODUCCION
						@endif
					</td>
					<td>
						@if($per->estado==1)
							Activo
						@else
							Inactivo
						@endif
					</td>
					<td>
						<a href="{{URL::action('ConfiguracionController@edit',$per->id)}}">
							<button class="btn btn-info">Editar</button>
						</a>
					</td>
				</tr>
				@include('seguridad.configuracion.modal')
				@endforeach
			</table>
		</div>
		{{$perfiles->render()}}
	</div>
</div>
@endsection