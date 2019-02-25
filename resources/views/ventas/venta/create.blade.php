@extends ('layouts.admin')
@section('contenido')
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<h3>Nuevo Venta <a href="{{asset('ventas/cliente/create')}}?v=1"><button class="btn btn-success"><i class="fa fa-plus-square"></i> Agregar Cliente</button></a></h3>
			
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

			{!!Form::open(array('url'=>'ventas/venta','method'=>'POST','autocomplete'=>'off'))!!}
			{{Form::token()}}	
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label>Tip. Comp.</label>
				<select name="tipo_comprobante" id="tipo_comprobante" class="form-control">
					<option>Seleccione</option>
					<option value="01">Factura</option>
					<option value="03">Boleta</option>
				</select>
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="serie_comprobante">Serie Comprobante</label>
				<input type="text" name="serie_comprobante" id="serie_comprobante" value="{{old('serie_comprobante')}}" class="form-control" readonly="true">
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="num_comprobante">Numero Comprobante</label>
				<input type="text" name="num_comprobante" id="num_comprobante" value="{{old('num_comprobante')}}" class="form-control" readonly="true">
			</div>
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
			<div class="form-group">
				<label for="proveedor">Moneda</label>
				<select name="moneda" class="form-control">
					<option value="01" selected>Soles</option>
					<option value="02">Dolares</option>
					<option value="03">Euros</option>
				</select>
			</div>
		</div>
		<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
			<div class="form-group">
				<label for="fecha">Fecha</label>
				<!-- <input type="text" name="fecha" value="{{old('num_comprobante')}}" class="form-control"> -->
				<div class="input-group date">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" class="form-control" id="fecha" name="fecha" value="<?php echo date("Y/m/d");?>">
                </div>
			</div>
		</div>
		<div class="col-lg-5 col-md-5 col-sm-5 col-xs-12">
			<div class="form-group">
				<label for="proveedor">Cliente</label>
				<select name="idcliente" id="idcliente" class="form-control selectpicker" data-live-search="true">
					<!-- @foreach($personas as $persona)
						<option value="{{$persona->idpersona}}">{{$persona->nombre}}</option>
					@endforeach -->
				</select>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="panel panel-primary">
			<div class="panel-body">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
					<div class="form-group">
						<label>Articulo</label>
						<select name="pidarticulo" class="form-control selectpicker" id="pidarticulo" data-live-search="true">
							@foreach($articulos as $articulo)
								<option value="{{$articulo->idarticulo}}_{{$articulo->stock}}_{{$articulo->precio_promedio}}">{{$articulo->articulo}}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
					<div class="form-group">
						<label for="cantidad">Cantidad</label>
						<input type="number" name="pcantidad" id="pcantidad" class="form-control" placeholder="cantidad">
					</div>
				</div>

				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
					<div class="form-group">
						<div class="form-group">
						<label for="precio_venta">Precio Venta</label>
						<input type="number" disabled name="pprecio_venta" id="pprecio_venta" class="form-control" placeholder="P. Venta">
					</div>	
					</div>
				</div>

				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
					<div class="form-group">
						<label for="descuento">Descuento</label>
						<input type="number" name="pdescuento" id="pdescuento" class="form-control" placeholder="Descuento">
					</div>
				</div>

				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
					<div class="form-group">
						<label for="stock">Stock</label>
						<input type="number" name="pstock" disabled id="pstock" class="form-control" placeholder="stock">
					</div>
				</div>

				

				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
					<div class="form-group">
						<div class="form-group">
							<button type="button" id="bt_add" class="btn btn-primary">Agregar</button>
						</div>	
					</div>
				</div>

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
				<button class="btn btn-info" type="reset">Limpiar</button>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group text-right">
				<a class="btn btn-danger" href="{{ asset('ventas/venta') }}">Cancelar</a>
			</div>
		</div>
	</div>
			
			{!!Form::close()!!}
	
@push('scripts')
<script>

	//Date picker
    

	$(document).ready(function(){
		$('#bt_add').click(function(){
			agregar();
		});
		$('#fecha').datepicker({
		  autoclose: true,
		});
		mostrarValores();
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
            // url: "/venta/peticion",
            url: "{{ asset('venta/peticion') }}",
            type: "post",
            headers: {'X-CSRF-TOKEN': token},
            // dataType: 'json',
            data: {'tipoComprobante': tc, "token": token},
            success: function (datos) {
               $("#serie_comprobante").val(datos['serie']);
               $("#num_comprobante").val(datos['correlativo']);
               clientes = datos['clientes']
               $('#idcliente').empty();
               clientes.forEach(function(element){
               		console.log(element.idpersona)
               		console.log(element.nombre)
               	
				$('#idcliente').append($('<option>', {value: element.idpersona, text: element.nombre}));
               })
               $('.selectpicker').selectpicker('refresh');


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

		console.log("STOCK: "+stock)

		if(idarticulo != "" && cantidad!="" && cantidad > 0 && descuento != "" && precio_venta != "") {
			if(parseInt(stock) >= parseInt(cantidad)){
				subtotal[cont] = (cantidad*precio_venta - descuento);
				total = total + subtotal[cont];
				var fila = '<tr class="selected" id="fila'+cont+'"><td><button type="button" class="btn btn-warning" onclick="eliminar('+cont+');">X</button></td><td><input type="hidden" name="idarticulo[]" value="'+idarticulo+'">'+articulo+'</td><td><input type="number" name="cantidad[]" value="'+cantidad+'"></td><td><input type="number" name="precio_venta[]" value="'+precio_venta+'"></td><td><input type="number" name="descuento[]" value="'+descuento+'"></td><td>'+subtotal[cont].toFixed(2)+'</td></tr>';
				cont++;
				limpiar();

				$("#total").html("S/. "+total.toFixed(2));

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
		$("#total_venta").val(total);
		$("#fila"+index).remove();
		evaluar();
	}
</script>
@endpush

@endsection