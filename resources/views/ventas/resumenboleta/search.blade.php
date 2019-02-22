{!! Form::open(array('url'=>'resumen/enviar','method'=>'POST', 'autocomplete'=>'off','role'=>'search'))!!}
<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label>Fecha Generacion de Comprobante</label>
				<div class="input-group date">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" class="form-control" id="fecha" name="txtFECHA_DOCUMENTO" value="<?php echo date("Y/m/d");?>" >
                </div>
				<input type="hidden" id="_token" name="_token" value="{{ csrf_token()}}"/>
				<!-- <input type="text" id="txtFECHA_DOCUMENTO" class="form-control" name="txtFECHA_DOCUMENTO" value="<?php //echo date("Y-m-d"); ?>" />	 -->
			</div>
		</div>

		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<button type="submit" class="btn btn-primary">Enviar</button>
			</div>
		</div>
	</div>

{{Form::close()}}