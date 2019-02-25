{!! Form::open(array('url'=>'reporte/compras','method'=>'POST', 'autocomplete'=>'off','role'=>'search'))!!}

	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Fecha Inicio</label>
				<div class="input-group date">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ $fecha_inicio }}" >
                </div>
				<input type="hidden" id="_token" name="_token" value="{{ csrf_token()}}"/>
			</div>
		</div>

		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label>Fecha Fin</label>
				<div class="row">
					<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
						<div class="input-group date">
		                  <div class="input-group-addon">
		                    <i class="fa fa-calendar"></i>
		                  </div>
		                  <input type="text" class="form-control" id="fecha_fin" name="fecha_fin" value="{{$fecha_fin}}" >
		                </div>
	                </div>
	                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
	            		<button type="submit" class="btn btn-primary">Listar</button>
	            		<!-- <button type="submit" class="btn btn-success">XLS</button> -->
	            		<button type="button" class="btn btn-success" id="exportarExcelCompras">XLS</button>
	                </div>
                </div>
			</div>
		</div>

	</div>

{{Form::close()}}

