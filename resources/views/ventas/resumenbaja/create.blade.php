@extends ('layouts.admin')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Nota Crédito/Débito</h3>
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

			{!!Form::open(array('url'=>'ventas/notas','method'=>'POST','autocomplete'=>'off'))!!}
			{{Form::token()}}	
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Tip. Comp.</label>
				<select name="tipo_comprobante" id="tipo_comprobante_n" class="form-control">
					<option>Seleccione</option>
					<option value="07">Nota Credito</option>
					<option value="08">Nota Debito</option>
				</select>
			</div>
		</div>

		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<label>N° Documento Modif.</label>
	                            

			<div class="row">                       
			<div class="col-lg-10">
			<input id="modifica" name="modifica" class="form-control valid" value="" type="text" readonly >
			<input type="hidden" name="smodifica" id="smodifica">
			<input type="hidden" name="nmodifica" id="nmodifica">
			<input type="hidden" name="tipodoc" id="tipodoc">
			</div>                   
			<div class="col-lg-2">
				<button type="button" class="btn btn-default btn-check-tc"><i class="fa fa-search"></i></button>
			</div>
			                   
			</div>
		</div>



		<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
			<div class="form-group">
				<label for="serie_comprobante">Serie</label>
				<input type="text" name="serie_comprobante" id="serie_comprobante" value="{{old('serie_comprobante')}}" class="form-control" readonly="true">
			</div>
		</div>
		<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
			<div class="form-group">
				<label for="num_comprobante">Numero</label>
				<input type="text" name="num_comprobante" id="num_comprobante" value="{{old('num_comprobante')}}" class="form-control" readonly="true">
			</div>
		</div>

		<div class="form-group col-lg-4 col-md-4 col-sm-6 col-xs-12">
			<label>Motivo:</label>
			<input type="hidden" name="motivod" id="motivod">
			<select name="motivo" id="motivo" class="form-control valid">
				<option value="">SELECCIONE</option>
			</select>
		</div>

		<div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12 padno2">
			<label>Moneda:</label>
			<input type="text" id="moneda" class="form-control" name="moneda" readonly value="" />
			<input name="valmoneda" type="hidden" id="valmoneda" >
		</div>

		<div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12 padno">
            <label>Fecha.Doc:</label>
			<input type="text" id="txtFECHA_DOCUMENTO" onChange="cargar_tc()" class="form-control" name="txtFECHA_DOCUMENTO" value="<?php echo date("Y-m-d"); ?>" />
		</div>


		<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<label>Cliente(*):</label>
			<input type="hidden" name="idcliente" id="idcliente">
			<input type="text" id="cliente" class="form-control" readonly name="cliente" value="" />
		</div>
		
	</div>
	<div class="row">
		<div class="panel panel-primary">
			<div class="panel-body">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<table id="detalles" class="table table-striped table-bordered table-condensed table-hover">
						<thead style="background-color:#A9D0F5">
							<th>Opciones</th>
							<th>Articulo</th>
							<th>Cantidad</th>
							<th>Precio Venta</th>
							<th>Descuento</th>							
							<th>Subtotal</th>
						</thead>
						<tbody>
							
						</tbody>
						<tfood>
							<th>TOTAL</th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th><h4 id="total">S/. 0.00</h4><input type="hidden" name="total_venta" id="total_venta"></th>
						</tfood>
						
					</table>
				</div>
			</div>
		</div>

		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12" id="guardar">
			<div class="form-group">
				<input type="hidden" id="_token" name="_token" value="{{ csrf_token()}}"></input>
				<button class="btn btn-primary" type="submit">Guardar</button>
				<button class="btn btn-danger" type="reset">Cancelar</button>
			</div>
		</div>
	</div>
			
			{!!Form::close()!!}
<div class="modal fade" id="modal-default" data-backdrop="static">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
		  <div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		      <span aria-hidden="true">&times;</span></button>
		    <h4 class="modal-title">Facturas / Boletas</h4>
		  </div>
		  <div class="modal-body">
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="table-responsive">

						<table id="example2" class="table table-striped table-bordered table-condensed table-hover">
							<thead>
								<th>Op.</th>
								<th>Serie</th>
								<th>Numero</th>
								<th>Fecha</th>
								<th>Estado</th>
							</thead>
							@foreach($comprobantes as $ven)
							<tr>
								<?php $dataCom = $ven->serie_comprobante."*".$ven->num_comprobante."*".$ven->nombre."*".$ven->moneda."*".$ven->tipo_comprobante."*".$ven->idpersona; ?>
								<td><button type="button" class="btn btn-success btn-xs btn-pick" value="<?php echo $dataCom; ?>"><i class="fa fa-plus"></i></button></td>
								<td>{{$ven->serie_comprobante}}</td>
								<td>{{$ven->num_comprobante}}</td>
								<td>{{$ven->fecha_hora}}</td>
								<td>@if($ven->response_code=='0')
											<a class="btn btn-success btn-xs">Aceptado</a>
										@else
											<a class="btn btn-danger btn-xs">Rechazado</a>
										@endif
								</td>

							</tr>
							@endforeach
						</table>
					</div>
				</div>
			</div>






		  </div>
		  <div class="modal-footer">

		  </div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-default-advertencia">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">SELECCIONES EL TIPO DE COMPROBANTE</h4>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
@push('scripts')
<script>

	var row_index="h";
	//Date picker
    

	$(document).ready(function(){
		$('#bt_add').click(function(){
			agregar();
		});
		$('#fecha').datepicker({
		  autoclose: true,
		});


		
	    $(document).on("onfocus", "#detalles tbody tr #cantidad", function() {
	        //some think
	        row_index = $(this).parent().parent().index()
	        console.log(row_index)
	    });

	});

	// $("table #detalles tbody tr #cantidad").click(function(e){
	// 	alert("Hola ");
	// })

	function calcular(x){
		cantidad = $("#cantidad"+x).val()
		precio_venta = $("#precio_venta"+x).val()
		descuento = $("#descuento"+x).val()

		subtotal[x] = (cantidad*precio_venta) - descuento


		console.log('Fila :'+x+' : '+subtotal[x])

		filas = $("#detalles tr").length-2;
		total = 0;
		for(var i = 0; i < filas ; i++){
			$("#subtotal"+i).html(subtotal[i]);
			total = total + subtotal[i];
			console.log('Fila :'+i+' : '+subtotal[i])
		}	
		$("#total").html(total);
	}

	$(document).on("click", ".btn-check-tc", function(){
        if($("#tipo_comprobante_n").val()=='07' || $("#tipo_comprobante_n").val()=='08'){
            $("#modal-default").modal('show');
        }else{
        	$("#modal-default-advertencia").modal('show');
        }
    });

    $(document).on("click", ".btn-pick", function(){
    	token = $("#_token").val();
        comprobante = $(this).val();
        infoCom = comprobante.split("*");
        console.log(infoCom)
        $("#modifica").val(infoCom[0]+'-'+infoCom[1]);
        $("#moneda").val(infoCom[3]);
        $("#cliente").val(infoCom[2]);
        $("#idcliente").val(infoCom[5]);
        $("#tipodoc").val(infoCom[4])
        $("#smodifica").val(infoCom[0])
        $("#nmodifica").val(infoCom[1])

        $('#motivod').val($("#motivo option:selected").text());
    	// console.log($("#motivo option:selected").text())

        $.ajax({
            url: "/notas/detalle",
            type: "post",
            headers: {'X-CSRF-TOKEN': token},
            // dataType: 'json',
            data: {'tipoComprobante': infoCom[4], 'serie':infoCom[0], 'num_comprobante':infoCom[1], "token": token},
            success: function (datos) {
            	eliminarFilas();
            	cont = 0;
				for(i in datos.detalle){
					subtotal[cont] = (datos.detalle[i].cantidad*datos.detalle[i].precio_venta - datos.detalle[i].descuento);
					total = total + subtotal[cont];
					var fila = '<tr class="selected" id="fila'+cont+'"><td>'+(cont+1)+'</td><td><input type="hidden" name="idarticulo[]" value="'+datos.detalle[i].idarticulo+'"" >'+datos.detalle[i].nombre+'</td><td><input type="number" id="cantidad'+cont+'" name="cantidad[]" value="'+datos.detalle[i].cantidad+'" onblur="calcular('+cont+')" ></td><td><input type="number" id="precio_venta'+cont+'" name="precio_venta[]" value="'+datos.detalle[i].precio_venta+'" onblur="calcular('+cont+')"></td><td><input type="number" id="descuento'+cont+'"name="descuento[]" value="'+datos.detalle[i].descuento+'" onblur="calcular('+cont+')"></td><td><label id="subtotal'+cont+'">'+subtotal[cont]+'</label></td></tr>';
					cont++;
					// limpiar();

					$("#total").html("S/. "+total);

					$("#total_venta").val(total);

					evaluar();

					$("#detalles").append(fila);
					
					// console.log(datos.detalle[i].nombre+" "+datos.detalle[i].cantidad+" "+datos.detalle[i].precio_venta+" "+datos.detalle[i].descuento)
				}
            },
            error: function (data) {
                console.log(data);
            }
        });


        $("#modal-default").modal('hide');
    });

    $("#motivo").change(function(){
    	$('#motivod').val($("#motivo option:selected").text());
    	console.log($("#motivo option:selected").text())
    });

    $("#tipo_comprobante_n").change(function () {
        token = $("#_token").val();
		tc = $("#tipo_comprobante_n").val();
		if($("#tipo_comprobante_n").val()=='07'){
			$('#motivo').empty();
			$('#motivo').append($('<option>', {value:'01', text:'ANULACION DE LA OPERACION'}));
			$('#motivo').append($('<option>', {value:'02', text:'ANULACION POR ERROR EN EL RUC'}));
			$('#motivo').append($('<option>', {value:'03', text:'CORRECION POR ERROR EN LA DESCRIPCION'}));
			$('#motivo').append($('<option>', {value:'04', text:'DESCUENTO GLOBAL'}));
			$('#motivo').append($('<option>', {value:'05', text:'DESCUENTO POR ITEM'}));
			$('#motivo').append($('<option>', {value:'06', text:'DEVOLUCION TOTAL'}));
			$('#motivo').append($('<option>', {value:'07', text:'DEVOLUCION POR ITEM'}));
			$('#motivo').append($('<option>', {value:'08', text:'BONIFICACION'}));
			$('#motivo').append($('<option>', {value:'09', text:'DISMINUCION EN EL VALOR'}));

		}
		if($("#tipo_comprobante_n").val()=='08'){
			$('#motivo').empty();
			$('#motivo').append($('<option>', {value:'01', text:'INTERES POR MORA'}));
			$('#motivo').append($('<option>', {value:'02', text:'AUMENTO EN EL VALOR'}));
			$('#motivo').append($('<option>', {value:'03', text:'PENALIDADES'}));
		}
		$.ajax({
            url: "/notas/peticion",
            type: "post",
            headers: {'X-CSRF-TOKEN': token},
            // dataType: 'json',
            data: {'tipoComprobante': tc, "token": token},
            success: function (datos) {
               $("#serie_comprobante").val(datos['serie']);
               $("#num_comprobante").val(datos['correlativo']);
               // console.log(datos)
            },
            error: function (data) {
                console.log(data);
            }
        });


    });

	var cont = 0;
	total = 0;
	subtotal = [];
	$("#guardar").hide();

	$("#pidarticulo").change(mostrarValores);

	function mostrarValores(){
		datosArticulo = document.getElementById('pidarticulo').value.split('_');
		$("#pprecio_venta").val(datosArticulo[2]);
		$("#pstock").val(datosArticulo[1]);
	}

	$("#tipo_comprobante").change(setNumCor);

	function setNumCor(){
		token = $("#_token").val();
		tc = $("#tipo_comprobante").val();
		// alert(tc)
		$.ajax({
            url: "/venta/peticion",
            type: "post",
            headers: {'X-CSRF-TOKEN': token},
            // dataType: 'json',
            data: {'tipoComprobante': tc, "token": token},
            success: function (datos) {
               $("#serie_comprobante").val(datos['serie']);
               $("#num_comprobante").val(datos['correlativo']);
               // console.log(datos)
            },
            error: function (data) {
                console.log(data);
            }
        });
		// datosArticulo = document.getElementById('pidarticulo').value.split('_');
		// $("#pprecio_venta").val(datosArticulo[2]);
		// $("#pstock").val(datosArticulo[1]);
	}

	function agregar(){

		datosArticulo = document.getElementById('pidarticulo').value.split('_');
		
		idarticulo = datosArticulo[0];
		articulo = $("#pidarticulo option:selected").text();
		cantidad = $("#pcantidad").val();
		descuento = $("#pdescuento").val();
		precio_venta = $("#pprecio_venta").val();
		stock = $('#pstock').val();

		if(idarticulo != "" && cantidad!="" && cantidad > 0 && descuento != "" && precio_venta != "") {
			if(stock >= cantidad){
				subtotal[cont] = (cantidad*precio_venta - descuento);
				total = total + subtotal[cont];
				var fila = '<tr class="selected" id="fila'+cont+'"><td><button type="button" class="btn btn-warning" onclick="eliminar('+cont+');">X</button></td><td><input type="hidden" name="idarticulo[]" value="'+idarticulo+'">'+articulo+'</td><td><input type="number" name="cantidad[]" value="'+cantidad+'"></td><td><input type="number" name="precio_venta[]" value="'+precio_venta+'"></td><td><input type="number" name="descuento[]" value="'+descuento+'"></td><td>'+subtotal[cont]+'</td></tr>';
				cont++;
				limpiar();

				$("#total").html("S/. "+total);

				$("#total_venta").val(total);

				evaluar();

				$("#detalles").append(fila);
			}else{
				alert("La cantidad a vender supera el stock"+stock+cantidad);
			}
		}else{
			alert("Error al ingresar el detalle de la venta revise los articulos");
		}
	}

	function limpiar(){
		$('#pcantidad').val('');
		$('#pdescuento').val('');
		$('#pprecio_venta').val('');
	}

	function evaluar(){
		if(total>0){
			$('#guardar').show();
		}else{
			$('#guardar').hide();
		}
	}

	function eliminar(index){
		total = total -subtotal[index];
		$("#total").html("S/."+total);
		$("#fila"+index).remove();
		evaluar();
	}
	function eliminarFilas(){
		filas = $("#detalles tr").length-2;
		total = 0.00;
		$("#total").html("S/."+total);
		for(var i = 0; i < filas ; i++){
			$("#fila"+i).remove();	
			// console.log("Eliminando Fila...."+i)
		}
	}


</script>
@endpush

@endsection