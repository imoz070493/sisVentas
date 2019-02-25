<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use sisVentas\Http\Requests;

use sisVentas\User;
use TCPDF;

class ExcelController extends Controller
{
    //

    public function enviarCorreo(){
    	$data = array(
    		'name' => "Curso Laraveldsadasdasd",
    	);
    	\Mail::send('ventas.venta.prueba',$data,function($message){
    		$message->from('imoz070493@gmail.com','Curso Laravel');
    		$message->to('mychy_7@hotmail.com')->subject('Test email Curso Laravel');

    		$xml = "C:/xampp1/htdocs/sisVentas/public/cdn/document/prueba21/20100066603-01-F001-00000190.ZIP";
    		$cdr = "C:/xampp1/htdocs/sisVentas/public/cdn/cdr/R-20100066603-01-F001-00000190.ZIP";
    		chmod($xml,0777);
    		chmod($cdr,0777);
    		$message->attach("C:/xampp1/htdocs/sisVentas/public/cdn/pdf/20100066603-01-F001-00000190.pdf");
    		$message->attach($xml);
    		$message->attach($cdr);
    	});

    	return "Tu email ha sido enviado satisfactoriamente";
    }

    public function exportUsersExcel(){
    	\Excel::create('Users', function($excel) {
	 
	    	$users = User::all();
	 	    $excel->sheet('Users', function($sheet) use($users) {
	 	    	//MODO 1:
	 	    	// $sheet->fromArray($users);

	 	    	//set general font style
                $sheet->setStyle(array(
                    'font' => array(
                        'name'      =>  'Calibri',
                        'size'      =>  15,
                        'bold'      =>  false,
                    )
                ));


                //set background to headers
                $sheet->cells('A1:E1', function($cells) {
 
                    $cells->setBackground('#000000')
                            ->setFontColor('#ffffff');
                    //set other properties
                });

	 	    	$sheet->row(1,['Numero','Nombre','Email','Fecha de Creacion','Fecha de Actualizacion']);
	 	    	foreach ($users as $index => $user) {
	 	    		$sheet->row($index+2,[$user->id,$user->name,$user->email,$user->created_at,$user->updated_at]);
	 	    	}
			});
	 
		})->export('xlsx');

    }

    public function exportUsersPdf(){
    	
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Nicola Asuni');
		$pdf->SetTitle('TCPDF Example 001');
		$pdf->SetSubject('TCPDF Tutorial');
		$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		$pdf->setFooterData(array(0,64,0), array(0,64,128));

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 14, '', true);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		// set text shadow effect
		$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

		// Set some content to print
		$html = 'EOD		<h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
		<i>This is the first example of TCPDF library.</i>
		<p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
		<p>Please check the source code documentation and other examples for further information.</p>
		<p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
		EOD';

		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

		// ---------------------------------------------------------

		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('example_001.pdf', 'I');
    }
}
