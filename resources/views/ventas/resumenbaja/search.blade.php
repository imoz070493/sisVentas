{!! Form::open(array('url'=>'resumenbaja/enviar','method'=>'POST', 'autocomplete'=>'off','role'=>'search'))!!}
<div class="form-group">
	<div class="form-group">
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<input type="hidden" id="_token" name="_token" value="{{ csrf_token()}}"></input>
				<input type="text" id="txtFECHA_DOCUMENTO" class="form-control" name="txtFECHA_DOCUMENTO" value="<?php echo date("Y-m-d"); ?>" />
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary">Enviar</button>
			</div>
	</div>
</div>

{{Form::close()}}