<?php
namespace sisVentas\Http\Controllers\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use DOMDocument;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

use sisVentas\Persona;
use sisVentas\Articulo;
use DB;

use Exception;
use Symfony\Component\Debug\Exception\FatalErrorException;
use stdClass;
use ArrayObject;


use TCPDF;
class Invoice
{    
	public function enviarFactura($factura){
        $id=0;
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba21\20563817161-01-F001-00004483.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba21\20100454523-01-F001-4355.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba\20480072872-RC-20171218-900.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba\20119453604-RA-20120416-2.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba21\20553510661-03-B001-1.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba21\20553510661-07-F001-1.ZIP';
        // $path = 'C:\xampp1\htdocs\facturalo_core_v3.6\public\cdn/document\prueba\10200545523-07-B011-0045.ZIP';
        // $RUTA = string("C:\xampp1\htdocs\sisVentas\public\cdn/document\prueba21\");
        $path = public_path().'\cdn/document/prueba21/'.$factura.'.ZIP';

        
        

        if(env('SYSTEM')=='linux')
            $nameExplode = explode('/', $path);
        elseif(env('SYSTEM')=='windows'){
            $nameExplode = explode('/', $path);
            //TODO fix var $name
        }

        $name = end($nameExplode);
        $nameExplode = explode('-', $name);
        //dd($name,$nameExplode);

        try {
            $sunatRequest = $this->newSunatRequest(env('SUNAT_SERVER'));
            // dd($sunatRequest);
            LOG::info("------------------ESTADO------------------".$sunatRequest['status']);
            if (!$sunatRequest['status']) {
                throw new Exception($sunatRequest['message']);
            }
            $client = $sunatRequest['client'];
            //dd($name);
            $content = file_get_contents($path);
            

            $params['contentFile'] = $content;
            $params['fileName'] = $name;

            
            switch ($nameExplode[1]) {
                case '01':
                case '03':
                case '07':
                case '08':

                    
                    $sendBill = $client->__soapCall('sendBill', array($params));
                    file_put_contents('cdn/test.txt', $client->__getLastRequest());


                    
                    switch (get_class($sendBill)) {
                        case 'stdClass':
                            $file = $sendBill->applicationResponse;

                            $nameCdr = 'R-' . $name;
                            $path = 'C:\xampp1\htdocs\sisVentas\public\cdn/cdr';

                            $pathFile = $path . DIRECTORY_SEPARATOR . $nameCdr;
                            

                            // $response['status'] = 1;
                            // $response['path'] = $responsePath = public_path($pathFile);
                            
                            $responsePath = $pathFile;
                            LOG::info("---------RESPONSE PATH---------".$responsePath);

                            file_exists($responsePath) ? unlink($responsePath) : '';
                            \File::put($responsePath, $file);
                            chmod($responsePath, 0777);

                            break;
                        case 'SoapFault':
                            LOG::info("------------------ERROR------------------");
                            $error = $sendBill->faultcode;
                            LOG::info($error);
                            $errorExplode = explode('.', $error);
                            $errorCode = end($errorExplode);
                            LOG::info($errorCode);
                            // $errErrorCode = ErrErrorCode::find($errorCode);
                            // throw new Exception($errErrorCode->c_description);
                    }
                    break;
                case 'RC':
                case 'RA':
                    // $sendSummary = $client->__soapCall('sendSummary', array($params));
                    // file_put_contents('cdn/test.txt', $client->__getLastRequest());                    
                    // // $docInvoiceFile->c_has_sunat_response = 'yes';
                    // // $docInvoiceFile->save();
                    // switch (get_class($sendSummary)) {
                    //     case 'stdClass':

                    //         LOG::info('-----------------RESPUESTA CORRECTA SUNAT-----------------');
                    //         LOG::info($sendSummary->ticket);

                    //         // $docInvoiceTicket = DocInvoiceTicket::find($id);
                    //         // $docInvoiceTicket->c_ticket = $sendSummary->ticket;
                    //         // $docInvoiceTicket->c_has_ticket = 'yes';
                    //         // $docInvoiceTicket->c_ticket_code = 98;
                    //         // $docInvoiceTicket->save();

                    //         // $response['status'] = 1;
                    //         // $response['ticket'] = $sendSummary->ticket;
                    //         break;
                    //     case 'SoapFault':
                    //         $error = $sendSummary->faultcode;
                    //         $errorExplode = explode('.', $error);
                    //         $errorCode = end($errorExplode);
                    //         $errErrorCode = ErrErrorCode::find($errorCode);
                    //         throw new Exception($errErrorCode->c_description);
                    // }
                    break;
            }

            Log::info('Envío a SUNAT',
                [
                'lgph_id' => 5, 'n_id_invoice' => $id, 'c_invoice_type_code' => $nameExplode[1]
                ]
            );
        } catch (Exception $exc) {
            \Session::get('fallo');//to put the session value
            // dd(\Session::get('fallo'));
            if(\Session::get('fallo')){
                $response['message'] = $exc->getMessage();
            }else{
                $response['message'] = $exc->getMessage()." CODE: ".$exc->faultcode;
            }
            
            $response['fallo'] = true;
            // return $response;
            Log::error($response['message'],
                [
                'lgph_id' => 5, 'n_id_invoice' => $id, 'c_invoice_type_code' => $nameExplode[1]
                ]
            );
        }
        
    }

    public function newSunatRequest($sunatServer)
    {
        \Session::put('fallo',false);//to put the session value
        
        $response['status'] = 0;
        $response['message'] = '';
        $response['client'] = '';
        try {

            $empresa = DB::table('config')
            ->where('estado','=','1')
            ->first();

            $user = $empresa->ruc.$empresa->usuario;
            $password = $empresa->clave;

            // $user = '20563817161MODDATOS';
            // $password = 'MODDATOS';
            $wsdl = null;
            switch ($sunatServer) {
                case 'beta':
                    # Beta nuevo
                    $wsdl = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService?wsdl';
                    # Beta antiguo
                    #$wsdl = 'https://www.sunat.gob.pe/ol-ti-itcpgem-beta/billService?wsdl';
                    break;
                case 'homologacion':
                    $wsdl = 'https://www.sunat.gob.pe/ol-ti-itcpgem-sqa/billService?wsdl';
                    break;
                case 'production':
                    # $wsdl = 'https://www.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl';
                    $wsdl = public_path('static/webservice/production.xml');
                    break;
                case 'cdr':
                    $wsdl = 'https://www.sunat.gob.pe/ol-it-wsconscpegem/billConsultService?wsdl';
                    break;
            }
            $auth = sprintf('<wsse:Security mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-' .
                '200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401' .
                '-wss-wssecurity-utility-1.0.xsd"><wsse:UsernameToken wsu:Id="UsernameToken-EBDA6BCF18BE1AEBD51424373' .
                '01228913"><wsse:Username>%s</wsse:Username><wsse:Password Type="http://docs.oasis-open.org/wss/2004/' .
                '01/oasis-200401-wss-username-token-profile-1.0#PasswordText">%s</wsse:Password></wsse:UsernameToken>' .
                '</wsse:Security>', $user, $password);

            $authValues = new \SoapVar($auth, XSD_ANYXML);
            $options = [
                'uri' => 'http://schemas.xmlsoap.org/soap/envelope/',
                'style' => SOAP_RPC,
                'use' => SOAP_ENCODED,
                'soap_version' => SOAP_1_1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 0,
                'trace' => true,
                'encoding' => 'UTF-8',
                'exceptions' => true,
            ];
            // if (!exec("ping -n 1 -w 1 ".'e-beta.sunat.gob.pe'." 2>NUL > NUL && (echo 0) || (echo 1)")){
            //     LOG::info("El host existe");
            // }
            // else {
            //     LOG::info("El host no está activo");
            // }

            $client = new \SoapClient($wsdl, $options);                

            $header = new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
                'Security', $authValues, false);
            $client->__setSoapHeaders($header);
            $response['status'] = 1;
            $response['client'] = $client;
            
        } 
        // catch (Exception $exc) {
        //     throw new RuntimeException('Could not connect to host:');
        //     return Redirect::to('/');
        //     // $response['message'] = $exc->getMessage();
        //     // LOG::info("SERVIDOR NO ENCONTRADO");
        // }
        catch(Exception $e) {
            // $var = null;
            // $msg = $e->getMessage();
            LOG::info("----------------------------------------------");
            \Session::put('fallo',true);//to put the session value
            return $response;
            // return Redirect::to('ventas/venta');
            // echo "Caught exception of class: " . get_class($e) . PHP_EOL;
        }


        $sunatRequest = $response;
        $client = $sunatRequest['client'];
        $params['ticket'] = '1535337237396';
        $getStatus = $client->__soapCall('getStatus', array($params));
        
        //dd($getStatus);
        return $response;
    }

    public static function buildInvoiceXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta,$empresa)
    {
    	$dom = new DOMDocument('1.0', 'UTF-8');
        #$dom->preserveWhiteSpace = false;
        // $dom->xmlStandalone = false;
        $dom->formatOutput = true;

        $invoice = $dom->createElement('Invoice');
        $newNode = $dom->appendChild($invoice);

        $newNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $newNode->setAttribute('xmlns:xsd','http://www.w3.org/2001/XMLSchema');
        $newNode->setAttribute('xmlns:cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $newNode->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $newNode->setAttribute('xmlns:ccts', 'urn:un:unece:uncefact:documentation:2');
        $newNode->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $newNode->setAttribute('xmlns:ext','urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $newNode->setAttribute('xmlns:qdt', 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2');
        $newNode->setAttribute('xmlns:udt','urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2');
        $newNode->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2'); 
        
        $ublExtensions = $dom->createElement('ext:UBLExtensions');
        $invoice->appendChild($ublExtensions);

        $ublExtension = $dom->createElement('ext:UBLExtension');
        $ublExtensions->appendChild($ublExtension);

        $extensionContent = $dom->createElement('ext:ExtensionContent');
        $ublExtension->appendChild($extensionContent);

        ////////////////////////////////////77

        $ublVersionID = $dom->createElement('cbc:UBLVersionID');
        $ublVersionID->nodeValue = '2.1';
        $invoice->appendChild($ublVersionID);

        $customizationID = $dom->createElement('cbc:CustomizationID');
        $schemeAgencyName =$dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $customizationID->appendChild($schemeAgencyName);
        $customizationID->nodeValue = '2.0';
        $invoice->appendChild($customizationID);

        #VARIABLE
        $profileID = $dom->createElement('cbc:ProfileID');
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Tipo de Operación';
        $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51';
        $profileID->appendChild($schemeName);
        $profileID->appendChild($schemeAgencyName);
        $profileID->appendChild($schemeURI);
        $profileID->nodeValue='0101';
        $invoice->appendChild($profileID);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $invoice->appendChild($cbcID);

        #VARIABLE
        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = $fecha;
        $invoice->appendChild($issueDate);

        #VARIABLE
        $issueTime = $dom->createElement('cbc:IssueTime');
        $issueTime->nodeValue = $hora;
        $invoice->appendChild($issueTime);

        #VARIABLE
        $dueDate = $dom->createElement('cbc:DueDate');
        $dueDate->nodeValue = $fecha;
        $invoice->appendChild($dueDate);

        #VARIABLE
        $invoiceTypeCode = $dom->createElement('cbc:InvoiceTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName= $dom->createAttribute('listName');
        $listName->value='Tipo de Documento';
        $listURI = $dom->createAttribute('listURI');
        $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01';
        $listID = $dom->createAttribute('listID');
        $listID->value='0101';
        $name = $dom->createAttribute('name');
        $name->value='Tipo de Operacion';
        $listSchemeURI = $dom->createAttribute('listSchemeURI');
        $listSchemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51';
        $invoiceTypeCode->appendChild($listAgencyName);
        $invoiceTypeCode->appendChild($listName);
        $invoiceTypeCode->appendChild($listURI);
        $invoiceTypeCode->appendChild($listID);
        $invoiceTypeCode->appendChild($name);
        $invoiceTypeCode->appendChild($listSchemeURI);
        $invoiceTypeCode->nodeValue = $tipo_comprobante;
        $invoice->appendChild($invoiceTypeCode);

        #VARIABLE
        $note = $dom->createElement('cbc:Note');
        $languageLocaleID = $dom->createAttribute('languageLocaleID');
        $languageLocaleID->value='1000';
        $note->appendChild($languageLocaleID);
        // $note->nodeValue='SEISCIENTOS TREINTA Y DOS CON 50/ 100';
        $note->nodeValue=$leyenda.' SOLES';
        $invoice->appendChild($note);
        
        #VARIABLE
        $documentCurrencyCode = $dom->createElement('cbc:DocumentCurrencyCode');
        $listID = $dom->createAttribute('listID');
        $listID->value='ISO 4217 Alpha';
        $listName =$dom->createAttribute('listName');
        $listName->value='Currency';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='United Nations Economic Commission for Europe';
        $documentCurrencyCode->appendChild($listID);
        $documentCurrencyCode->appendChild($listName);
        $documentCurrencyCode->appendChild($listAgencyName);
        $documentCurrencyCode->nodeValue = 'PEN';
        $invoice->appendChild($documentCurrencyCode);

        #VARIABLE
        $lineCountNumeric = $dom->createElement('cbc:LineCountNumeric');
        $lineCountNumeric->nodeValue=count($idarticulo);
        $invoice->appendChild($lineCountNumeric);

        #VARIABLE
        // $orderReference = $dom->createElement('cac:OrderReference');
        // $invoice->appendChild($orderReference);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue='0004-00028955';
        // $orderReference->appendChild($cbcID);

        #VARIABLE
        // $despatchDocumentReference = $dom->createElement('cac:DespatchDocumentReference');
        // $invoice->appendChild($despatchDocumentReference);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue='0003-00051304';
        // $despatchDocumentReference->appendChild($cbcID);        

        // $issueDate = $dom->createElement('cbc:IssueDate');
        // $issueDate->nodeValue = '2018-04-02';
        // $despatchDocumentReference->appendChild($issueDate);

        // $documentTypeCode = $dom->createElement('cbc:DocumentTypeCode');
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listName =$dom->createAttribute('listName');
        // $listName->value='Tipo de Documento';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01';
        // $documentTypeCode->appendChild($listAgencyName);
        // $documentTypeCode->appendChild($listName);
        // $documentTypeCode->appendChild($listURI);
        // $documentTypeCode->nodeValue = '09';
        // $despatchDocumentReference->appendChild($documentTypeCode);

        $signature = $dom->createElement('cac:Signature');
        $invoice->appendChild($signature);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = 'IDSignSP';
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $signature->appendChild($cbcID);

        $signatoryParty = $dom->createElement('cac:SignatoryParty');
        $signature->appendChild($signatoryParty);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $signatoryParty->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $signatoryParty->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = $empresa->razon_social;
        $partyName->appendChild($cbcName);

        $digitalSignatoreAttachment = $dom->createElement('cac:DigitalSignatureAttachment');
        $signature->appendChild($digitalSignatoreAttachment);

        $externalReference = $dom->createElement('cac:ExternalReference');
        $digitalSignatoreAttachment->appendChild($externalReference);

        #VARIABLE
        $cbcURI = $dom->createElement('cbc:URI');
        $cbcURI->nodeValue = '#'.$serie_comprobante.'-'.$num_comprobante;
        $externalReference->appendChild($cbcURI);

        $accountSupplierParty = $dom->createElement('cac:AccountingSupplierParty');
        $invoice->appendChild($accountSupplierParty);

        $party = $dom->createElement('cac:Party');
        $accountSupplierParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $acbcName = $dom->createCDATASection($empresa->nombre_comercial);
        // $cbcName->nodeValue='CORPORACION BJR PRUEBA';
        $cbcName->appendChild($acbcName);
        $partyName->appendChild($cbcName);

        $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        $party->appendChild($partyTaxScheme);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aRegistrationName);
        $partyTaxScheme->appendChild($registrationName);

        #VARIABLE
        $companyID = $dom->createElement('cbc:CompanyID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $companyID->appendChild($schemeID);
        $companyID->appendChild($schemeName);
        $companyID->appendChild($schemeAgencyName);
        $companyID->appendChild($schemeURI);
        $companyID->nodeValue = $empresa->ruc;
        $partyTaxScheme->appendChild($companyID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $partyTaxScheme->appendChild($taxScheme);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $empresa->ruc;
        $taxScheme->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aRegistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $registrationAddress = $dom->createElement('cac:RegistrationAddress');
        $partyLegalEntity->appendChild($registrationAddress);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Ubigeos';
        $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:INEI';
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $registrationAddress->appendChild($cbcID);

        #VARIABLE
        $addressTypeCode = $dom->createElement('cbc:AddressTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName = $dom->createAttribute('listName');
        $listName->value='Establecimientos anexos';
        $addressTypeCode->appendChild($listAgencyName);
        $addressTypeCode->appendChild($listName);
        $addressTypeCode->nodeValue='0000';
        $registrationAddress->appendChild($addressTypeCode);

        #VARIABLE
        $cityName = $dom->createElement('cbc:CityName');
        $acityName = $dom->createCDATASection($empresa->departamento);
        $cityName->appendChild($acityName);
        $registrationAddress->appendChild($cityName);

        #VARIABLE
        $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        $acountrySubentity = $dom->createCDATASection($empresa->provincia);
        $countrySubentity->appendChild($acountrySubentity);
        $registrationAddress->appendChild($countrySubentity);

        #VARIABLE
        $district = $dom->createElement('cbc:District');
        $adistrict = $dom->createCDATASection($empresa->distrito);
        $district->appendChild($adistrict);
        $registrationAddress->appendChild($district);

        $addressLine = $dom->createElement('cac:AddressLine');
        $registrationAddress->appendChild($addressLine);

        #VARIABLE
        $line = $dom->createElement('cbc:Line');
        $aline = $dom->createCDATASection($empresa->direccion);
        $line->appendChild($aline);
        $addressLine->appendChild($line);

        $country = $dom->createElement('cac:Country');
        $registrationAddress->appendChild($country);

        $identificationCode = $dom->createElement('cbc:IdentificationCode');
        $listID = $dom->createAttribute('listID');
        $listID->value='ISO 3166-1';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='United Nations Economic Commission for Europe';
        $listName = $dom->createAttribute('listName');
        $listName->value='Country';
        $identificationCode->appendChild($listID);
        $identificationCode->appendChild($listAgencyName);
        $identificationCode->appendChild($listName);
        $identificationCode->nodeValue='PE';
        $country->appendChild($identificationCode);

        // $contact = $dom->createElement('cac:Contact');
        // $party->appendChild($contact);

        // $name = $dom->createElement('cbc:Name');
        // $name->nodeValue='MIGUEL DELGADO';
        // $contact->appendChild($name);

        $query = Persona::findOrFail($idcliente);

        $accountCustomerParty = $dom->createElement('cac:AccountingCustomerParty');
        $invoice->appendChild($accountCustomerParty);

        $party = $dom->createElement('cac:Party');
        $accountCustomerParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $query->num_documento;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $aCbcName = $dom->createCDATASection($query->nombre);
        $cbcName->appendChild($aCbcName);
        $partyName->appendChild($cbcName);

        $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        $party->appendChild($partyTaxScheme);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $registrationName->appendChild($dom->createCDATASection($query->nombre));
        $partyTaxScheme->appendChild($registrationName);

        #VARIABLE
        $companyID = $dom->createElement('cbc:CompanyID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $companyID->appendChild($schemeID);
        $companyID->appendChild($schemeName);
        $companyID->appendChild($schemeAgencyName);
        $companyID->appendChild($schemeURI);
        $companyID->nodeValue = $query->num_documento;
        $partyTaxScheme->appendChild($companyID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $partyTaxScheme->appendChild($taxScheme);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $query->num_documento;
        $taxScheme->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($query->nombre);
        $registrationName->appendChild($aRegistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $registrationAddress = $dom->createElement('cac:RegistrationAddress');
        $partyLegalEntity->appendChild($registrationAddress);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Ubigeos';
        $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:INEI';
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        // $cbcID->nodeValue='010701';
        $registrationAddress->appendChild($cbcID);

        $cityName = $dom->createElement('cbc:CityName');
        $acityName = $dom->createCDATASection('');
        $cityName->appendChild($acityName);
        $registrationAddress->appendChild($cityName);

        $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        $acountrySubentity = $dom->createCDATASection('');
        $countrySubentity->appendChild($acountrySubentity);
        $registrationAddress->appendChild($countrySubentity);

        $district = $dom->createElement('cbc:District');
        $adistrict = $dom->createCDATASection('');
        $district->appendChild($adistrict);
        $registrationAddress->appendChild($district);


        $addressLine = $dom->createElement('cac:AddressLine');
        $registrationAddress->appendChild($addressLine);

        #VARIABLE
        $line = $dom->createElement('cbc:Line');
        $aline = $dom->createCDATASection($query->direccion);
        $line->appendChild($aline);
        $addressLine->appendChild($line);

        $country = $dom->createElement('cac:Country');
        $registrationAddress->appendChild($country);

        $identificationCode = $dom->createElement('cbc:IdentificationCode');
        $listID = $dom->createAttribute('listID');
        $listID->value='ISO 3166-1';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='United Nations Economic Commission for Europe';
        $listName = $dom->createAttribute('listName');
        $listName->value='Country';
        $identificationCode->appendChild($listID);
        $identificationCode->appendChild($listAgencyName);
        $identificationCode->appendChild($listName);
        $identificationCode->nodeValue='PE';
        $country->appendChild($identificationCode);

        $allowanceCharge = $dom->createElement('cac:AllowanceCharge');
        $invoice->appendChild($allowanceCharge);

        $chargeIndicator = $dom->createElement('cbc:ChargeIndicator');
        $chargeIndicator->nodeValue='false';
        $allowanceCharge->appendChild($chargeIndicator);

        $allowanceChargeReasonCode = $dom->createElement('cbc:AllowanceChargeReasonCode');
        $listName = $dom->createAttribute('listName');
        $listName->value='Cargo/descuento';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listURI = $dom->createAttribute('listURI');
        $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo53';
        $allowanceChargeReasonCode->appendChild($listName);
        $allowanceChargeReasonCode->appendChild($listAgencyName);
        $allowanceChargeReasonCode->appendChild($listURI);
        $allowanceChargeReasonCode->nodeValue = '02';
        $allowanceCharge->appendChild($allowanceChargeReasonCode);

        $multiplierFactorNumeric = $dom->createElement('cbc:MultiplierFactorNumeric');
        $multiplierFactorNumeric->nodeValue = '0.00';
        $allowanceCharge->appendChild($multiplierFactorNumeric);

        $amount = $dom->createElement('cbc:Amount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $amount->appendChild($currencyID);
        $amount->nodeValue='0.00';
        $allowanceCharge->appendChild($amount);

        $baseAmount = $dom->createElement('cbc:BaseAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $baseAmount->appendChild($currencyID);
        $baseAmount->nodeValue='0.00';
        $allowanceCharge->appendChild($baseAmount);


        $neto = round(($total_venta/1.18),2);
        $igv = $total_venta-$neto;

        $taxTotal = $dom->createElement('cac:TaxTotal');
        $invoice->appendChild($taxTotal);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxTotal->appendChild($taxAmount);

        //////////////////////////////////////////////////////

        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        $taxTotal->appendChild($taxSubTotal);

        #VARIABLE
        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $taxableAmount->appendChild($currencyID);
        $taxableAmount->nodeValue=$neto;
        $taxSubTotal->appendChild($taxableAmount);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxSubTotal->appendChild($taxAmount);

        $taxCategory = $dom->createElement('cac:TaxCategory');
        $taxSubTotal->appendChild($taxCategory);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='UN/ECE 5305';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Tax Category Identifier';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='United Nations Economic Commission for Europe';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->nodeValue = 'S';
        $taxCategory->appendChild($cbcID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $taxCategory->appendChild($taxScheme);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='UN/ECE 5153';
        $schemeAgencyID = $dom->createAttribute('schemeAgencyID');
        $schemeAgencyID->value='6';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeAgencyID);
        $cbcID->nodeValue = '1000';
        $taxScheme->appendChild($cbcID);

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = 'IGV';
        $taxScheme->appendChild($cbcName);

        $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
        $taxTypeCode->nodeValue = 'VAT';
        $taxScheme->appendChild($taxTypeCode);

        $legalMonetaryTotal = $dom->createElement('cac:LegalMonetaryTotal');
        $invoice->appendChild($legalMonetaryTotal);

        #VARIABLE
        $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $lineExtensionAmount->appendChild($currencyID);
        $lineExtensionAmount->nodeValue=$neto;
        $legalMonetaryTotal->appendChild($lineExtensionAmount);

        #VARIABLE
        $taxInclusiveAmount = $dom->createElement('cbc:TaxInclusiveAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $taxInclusiveAmount->appendChild($currencyID);
        $taxInclusiveAmount->nodeValue=number_format(round($total_venta,2),2,'.','');
        $legalMonetaryTotal->appendChild($taxInclusiveAmount);

        #VARIABLE
        $allowanceTotalAmount = $dom->createElement('cbc:AllowanceTotalAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $allowanceTotalAmount->appendChild($currencyID);
        $allowanceTotalAmount->nodeValue='0.00';
        $legalMonetaryTotal->appendChild($allowanceTotalAmount);

        #VARIABLE
        $chargeTotalAmount = $dom->createElement('cbc:ChargeTotalAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $chargeTotalAmount->appendChild($currencyID);
        $chargeTotalAmount->nodeValue='0.00';
        $legalMonetaryTotal->appendChild($chargeTotalAmount);

        #VARIABLE
        $payableAmount = $dom->createElement('cbc:PayableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $payableAmount->appendChild($currencyID);
        $payableAmount->nodeValue=number_format(round($total_venta,2),2,'.','');
        $legalMonetaryTotal->appendChild($payableAmount);

        //INVOICE LINE 1

        $cont = 1;
		while($cont <= count($idarticulo)){
            $totalItem = $cantidad[$cont-1]*$precio_venta[$cont-1];

			$neto_item = $totalItem/1.18;
        	$impuesto = $totalItem-$neto_item;

			$invoiceLine = $dom->createElement('cac:InvoiceLine');
	        $invoice->appendChild($invoiceLine);

            #VARIABLE
	        $cbcID = $dom->createElement('cbc:ID');
	        $cbcID->nodeValue = $cont;
	        $invoiceLine->appendChild($cbcID);

            #VARIABLE
	        $invoicedQuantity = $dom->createElement('cbc:InvoicedQuantity');
	        $aInvoicedQuantity = $dom->createAttribute('unitCode');
	        $aInvoicedQuantity->value = 'NIU';
	        $unitCodeList = $dom->createAttribute('unitCodeListID');
	        $unitCodeList->value='UN/ECE rec 20';
	        $unitCodeListAgencyName = $dom->createAttribute('unitCodeListAgencyName');
	        $unitCodeListAgencyName->value='United Nations Economic Commission for Europe';
	        $invoicedQuantity->appendChild($aInvoicedQuantity);
	        $invoicedQuantity->appendChild($unitCodeList);
	        $invoicedQuantity->appendChild($unitCodeListAgencyName);
	        $invoicedQuantity->nodeValue = $cantidad[$cont-1];
	        $invoiceLine->appendChild($invoicedQuantity);

            #VARIABLE
	        $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
	        $aLineExtensionAmount = $dom->createAttribute('currencyID');
	        $aLineExtensionAmount->value = 'PEN';
	        $lineExtensionAmount->appendChild($aLineExtensionAmount);
	        $lineExtensionAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
	        $invoiceLine->appendChild($lineExtensionAmount);

	        $pricingReference = $dom->createElement('cac:PricingReference');
	        $invoiceLine->appendChild($pricingReference);

	        $alternativeConditionPrice = $dom->createElement('cac:AlternativeConditionPrice');
	        $pricingReference->appendChild($alternativeConditionPrice);

            #VARIABLE
	        $priceAmount = $dom->createElement('cbc:PriceAmount');
	        $aPriceAmount = $dom->createAttribute('currencyID');
	        $aPriceAmount->value = 'PEN';
	        $priceAmount->appendChild($aPriceAmount);
	        $priceAmount->nodeValue = number_format(round($precio_venta[$cont-1],2),2,'.','');
	        $alternativeConditionPrice->appendChild($priceAmount);

            #VARIABLE
	        $priceTypeCode = $dom->createElement('cbc:PriceTypeCode');
	        $listName = $dom->createAttribute('listName');
	        $listName->value='Tipo de Precio';
	        $listAgencyName = $dom->createAttribute('listAgencyName');
	        $listAgencyName->value='PE:SUNAT';
	        $listURI = $dom->createAttribute('listURI');
	        $listURI->value = 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16';
	        $priceTypeCode->appendChild($listName);
	        $priceTypeCode->appendChild($listAgencyName);
	        $priceTypeCode->appendChild($listURI);
	        $priceTypeCode->nodeValue = '01';
	        $alternativeConditionPrice->appendChild($priceTypeCode);

	        $taxTotal = $dom->createElement('cac:TaxTotal');
	        $invoiceLine->appendChild($taxTotal);

            #VARIABLE
	        $taxAmount = $dom->createElement('cbc:TaxAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxAmount->appendChild($aTaxAmount);
	        $taxAmount->nodeValue = number_format(round($impuesto,2),2,'.','');
	        $taxTotal->appendChild($taxAmount);

	        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
	        $taxTotal->appendChild($taxSubTotal);

            #VARIABLE
	        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxableAmount->appendChild($aTaxAmount);
	        $taxableAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
	        $taxSubTotal->appendChild($taxableAmount);

            #VARIABLE
	        $taxAmount = $dom->createElement('cbc:TaxAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxAmount->appendChild($aTaxAmount);
	        $taxAmount->nodeValue = number_format(round($impuesto,2),2,'.','');
	        $taxSubTotal->appendChild($taxAmount);

	        $taxCategory = $dom->createElement('cac:TaxCategory');
	        $taxSubTotal->appendChild($taxCategory);

            #VARIABLE
	        $cbcID = $dom->createElement('cbc:ID');
	        $schemeID = $dom->createAttribute('schemeID');
	        $schemeID->value='UN/ECE 5305';
	        $schemeName = $dom->createAttribute('schemeName');
	        $schemeName->value='Tax Category Identifier';
	        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
	        $schemeAgencyName->value='United Nations Economic Commission for Europe';
	        $cbcID->appendChild($schemeID);
	        $cbcID->appendChild($schemeName);
	        $cbcID->appendChild($schemeAgencyName);
	        $cbcID->nodeValue = 'S';
	        $taxCategory->appendChild($cbcID);

	        $cbcPercent = $dom->createElement('cbc:Percent');
	        $cbcPercent->nodeValue='18.00';
	        $taxCategory->appendChild($cbcPercent);

            #VARIABLE
	        $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
	        $listAgencyName = $dom->createAttribute('listAgencyName');
	        $listAgencyName->value='PE:SUNAT';
	        $listName = $dom->createAttribute('listName');
	        $listName->value='SUNAT:Codigo de Tipo de Afectación del IGV';
	        $listURI = $dom->createAttribute('listURI');
	        $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07';
	        $taxExemptionReasonCode->appendChild($listAgencyName);
	        $taxExemptionReasonCode->appendChild($listName);
	        $taxExemptionReasonCode->appendChild($listURI);
	        $taxExemptionReasonCode->nodeValue = '10';
	        $taxCategory->appendChild($taxExemptionReasonCode);

	        $taxScheme = $dom->createElement('cac:TaxScheme');
	        $taxCategory->appendChild($taxScheme);

	        $cbcID = $dom->createElement('cbc:ID');
	        $schemeID = $dom->createAttribute('schemeID');
	        $schemeID->value='UN/ECE 5153';
	        $schemeName = $dom->createAttribute('schemeName');
	        $schemeName->value='Tax Scheme Identifier';
	        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
	        $schemeAgencyName->value='United Nations Economic Commission for Europe';
	        $cbcID->appendChild($schemeID);
	        $cbcID->appendChild($schemeName);
	        $cbcID->appendChild($schemeAgencyName);
	        $cbcID->nodeValue = '1000';
	        $taxScheme->appendChild($cbcID);

	        $cbcName = $dom->createElement('cbc:Name');
	        $cbcName->nodeValue = 'IGV';
	        $taxScheme->appendChild($cbcName);

	        $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
	        $taxTypeCode->nodeValue = 'VAT';
	        $taxScheme->appendChild($taxTypeCode);

	        $item = $dom->createElement('cac:Item');
	        $invoiceLine->appendChild($item);

	        $articulo = Articulo::findOrFail($idarticulo[$cont-1]);

            #VARIABLE
	        $description = $dom->createElement('cbc:Description');
            $adescription = $dom->createCDATASection($articulo->nombre);
            $description->appendChild($adescription);
	        // $description->nodeValue = $articulo->nombre;
	        $item->appendChild($description);

	        // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
	        // $item->appendChild($sellersItemIdentification);

	        // $cbcID = $dom->createElement('cbc:ID');
	        // $cbcID->nodeValue = 'CBJI300-18-BT';
	        // $sellersItemIdentification->appendChild($cbcID);

	        $price = $dom->createElement('cac:Price');
	        $invoiceLine->appendChild($price);

	        $priceAmount = $dom->createElement('cbc:PriceAmount');
	        $aPriceAmount = $dom->createAttribute('currencyID');
	        $aPriceAmount->value = 'PEN';
	        $priceAmount->appendChild($aPriceAmount);
	        $priceAmount->nodeValue= number_format(round($precio_venta[$cont-1],2),2,'.','');
	        $price->appendChild($priceAmount);

			$cont++;
		}

        

        //INVOICE LINE 2

        // $invoiceLine = $dom->createElement('cac:InvoiceLine');
        // $invoice->appendChild($invoiceLine);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = '2';
        // $invoiceLine->appendChild($cbcID);

        // $invoicedQuantity = $dom->createElement('cbc:InvoicedQuantity');
        // $aInvoicedQuantity = $dom->createAttribute('unitCode');
        // $aInvoicedQuantity->value = 'NIU';
        // $unitCodeList = $dom->createAttribute('unitCodeListID');
        // $unitCodeList->value='UN/ECE rec 20';
        // $unitCodeListAgencyName = $dom->createAttribute('unitCodeListAgencyName');
        // $unitCodeListAgencyName->value='United Nations Economic Commission for Europe';
        // $invoicedQuantity->appendChild($aInvoicedQuantity);
        // $invoicedQuantity->appendChild($unitCodeList);
        // $invoicedQuantity->appendChild($unitCodeListAgencyName);
        // $invoicedQuantity->nodeValue = '20.0000';
        // $invoiceLine->appendChild($invoicedQuantity);

        // $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
        // $aLineExtensionAmount = $dom->createAttribute('currencyID');
        // $aLineExtensionAmount->value = 'PEN';
        // $lineExtensionAmount->appendChild($aLineExtensionAmount);
        // $lineExtensionAmount->nodeValue = '398.31';
        // $invoiceLine->appendChild($lineExtensionAmount);

        // $pricingReference = $dom->createElement('cac:PricingReference');
        // $invoiceLine->appendChild($pricingReference);

        // $alternativeConditionPrice = $dom->createElement('cac:AlternativeConditionPrice');
        // $pricingReference->appendChild($alternativeConditionPrice);

        // $priceAmount = $dom->createElement('cbc:PriceAmount');
        // $aPriceAmount = $dom->createAttribute('currencyID');
        // $aPriceAmount->value = 'PEN';
        // $priceAmount->appendChild($aPriceAmount);
        // $priceAmount->nodeValue = '23.50';
        // $alternativeConditionPrice->appendChild($priceAmount);

        // $priceTypeCode = $dom->createElement('cbc:PriceTypeCode');
        // $listName = $dom->createAttribute('listName');
        // $listName->value='Tipo de Precio';
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value = 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16';
        // $priceTypeCode->appendChild($listName);
        // $priceTypeCode->appendChild($listAgencyName);
        // $priceTypeCode->appendChild($listURI);
        // $priceTypeCode->nodeValue = '01';
        // $alternativeConditionPrice->appendChild($priceTypeCode);

        // $taxTotal = $dom->createElement('cac:TaxTotal');
        // $invoiceLine->appendChild($taxTotal);

        // $taxAmount = $dom->createElement('cbc:TaxAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxAmount->appendChild($aTaxAmount);
        // $taxAmount->nodeValue = '71.70';
        // $taxTotal->appendChild($taxAmount);

        // $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        // $taxTotal->appendChild($taxSubTotal);

        // $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxableAmount->appendChild($aTaxAmount);
        // $taxableAmount->nodeValue = '398.31';
        // $taxSubTotal->appendChild($taxableAmount);

        // $taxAmount = $dom->createElement('cbc:TaxAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxAmount->appendChild($aTaxAmount);
        // $taxAmount->nodeValue = '71.70';
        // $taxSubTotal->appendChild($taxAmount);

        // $taxCategory = $dom->createElement('cac:TaxCategory');
        // $taxSubTotal->appendChild($taxCategory);

        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value='UN/ECE 5305';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Tax Category Identifier';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='United Nations Economic Commission for Europe';
        // $cbcID->appendChild($schemeID);
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // $cbcID->nodeValue = 'S';
        // $taxCategory->appendChild($cbcID);

        // $cbcPercent = $dom->createElement('cbc:Percent');
        // $cbcPercent->nodeValue='18.00';
        // $taxCategory->appendChild($cbcPercent);

        // $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listName = $dom->createAttribute('listName');
        // $listName->value='SUNAT:Codigo de Tipo de Afectación del IGV';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07';
        // $taxExemptionReasonCode->appendChild($listAgencyName);
        // $taxExemptionReasonCode->appendChild($listName);
        // $taxExemptionReasonCode->appendChild($listURI);
        // $taxExemptionReasonCode->nodeValue = '10';
        // $taxCategory->appendChild($taxExemptionReasonCode);

        // $taxScheme = $dom->createElement('cac:TaxScheme');
        // $taxCategory->appendChild($taxScheme);

        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value='UN/ECE 5153';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Tax Scheme Identifier';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='United Nations Economic Commission for Europe';
        // $cbcID->appendChild($schemeID);
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // $cbcID->nodeValue = '1000';
        // $taxScheme->appendChild($cbcID);

        // $cbcName = $dom->createElement('cbc:Name');
        // $cbcName->nodeValue = 'IGV';
        // $taxScheme->appendChild($cbcName);

        // $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
        // $taxTypeCode->nodeValue = 'VAT';
        // $taxScheme->appendChild($taxTypeCode);

        // $item = $dom->createElement('cac:Item');
        // $invoiceLine->appendChild($item);

        // $description = $dom->createElement('cbc:Description');
        // $description->nodeValue = 'LLANTA 350-8 SU-037 4PR|';
        // $item->appendChild($description);

        // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
        // $item->appendChild($sellersItemIdentification);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = 'SU350-8-037-4PR';
        // $sellersItemIdentification->appendChild($cbcID);

        // $price = $dom->createElement('cac:Price');
        // $invoiceLine->appendChild($price);

        // $priceAmount = $dom->createElement('cbc:PriceAmount');
        // $aPriceAmount = $dom->createAttribute('currencyID');
        // $aPriceAmount->value = 'PEN';
        // $priceAmount->appendChild($aPriceAmount);
        // $priceAmount->nodeValue='19.92';
        // $price->appendChild($priceAmount);






        // $xmlName = sprintf('%s-%s-%s', "Prueba", '01',1);
        $xmlName = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
        $xmlPath = public_path().'\cdn/xml/'. $xmlName . '.XML';
        $xmlFullPath = $xmlPath;
        file_exists($xmlFullPath) ? unlink($xmlFullPath) : '';
        \File::put($xmlFullPath, $dom->saveXML());
        chmod($xmlFullPath, 0777);


        // $privateKey = storage_path('sunat/keys/LLAVE_PRIVADA.pem');
        // $publicKey = storage_path('sunat/keys/LLAVE_PUBLICA.pem');
        // if (!file_exists($privateKey))
        //     throw new Exception('No se encuentra la LLAVE PRIVADA');
        // if (!file_exists($publicKey))
        //     throw new Exception('No se encuentra la LLAVE PUBLICA');                    

        // $cmdString = sprintf('xmlsec1 --sign --privkey-pem %s,%s --output %s %s', $privateKey, $publicKey,
        //     $xmlFullPath, $xmlFullPath);
        // exec($cmdString);

        // while (!file_exists($xmlFullPath)) {
        //     sleep(1);
        //     if (file_exists($xmlFullPath)) {
        //         break;
        //     }
        // }
        // chmod($xmlFullPath, 0777);


        //SEGUNDA FORMA DE FIRMAR UN DOCUMENTO 

        // $privateKey = storage_path('sunat/keys/PrivateKey.key');
        // $publicKey = storage_path('sunat/keys/ServerCertificate.cer');

        $privateKey = storage_path('sunat/keys/ros.key');
        $publicKey = storage_path('sunat/keys/ros.cer');

        if (!file_exists($privateKey))
            throw new Exception('No se encuentra la LLAVE PRIVADA');
        if (!file_exists($publicKey))
            throw new Exception('No se encuentra la LLAVE PUBLICA');

        $ReferenceNodeName = 'ExtensionContent';

        // Load the XML to be signed
        $doc = new DOMDocument();
        $doc->load($xmlFullPath);

        // Create a new Security object
        $objDSig = new XMLSecurityDSig();
        // Use the c14n exclusive canonicalization
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc, 
            XMLSecurityDSig::SHA1, 
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
            $options = array('force_uri' => true)
        );

        // Create a new (private) Security key
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
        /*
        If key has a passphrase, set it using
        $objKey->passphrase = '<passphrase>';
        */

        // Load the private key
        $objKey->loadKey($privateKey, TRUE);
        //$objKey->loadKey('certificates/PrivateKey.key', TRUE);

        // Sign the XML file
        $objDSig->sign($objKey,$doc->getElementsByTagName($ReferenceNodeName)->item(0));

        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($publicKey));
        //$objDSig->add509Cert(file_get_contents('certificates/ServerCertificate.cer'));
        // Append the signature to the XML
        //die(var_dump($doc->documentElement));
        $objDSig->appendSignature($doc->getElementsByTagName($ReferenceNodeName)->item(0));
        //$objDSig->appendSignature($ReferenceNodeName);
        // Save the signed XML
        $doc->save($xmlFullPath);

        /////////////
        

        $zipPath = 'cdn/document/prueba21' . DIRECTORY_SEPARATOR . $xmlName . '.ZIP';
        $zipFullPath = public_path($zipPath);

        file_exists($zipFullPath) ? unlink($zipFullPath) : '';
            \Zipper::make($zipFullPath)->add($xmlFullPath)->close();
            unlink($xmlFullPath);
    }

    public static function buildInvoiceXmlB($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta,$empresa)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        #$dom->preserveWhiteSpace = false;
        // $dom->xmlStandalone = false;
        $dom->formatOutput = true;

        $invoice = $dom->createElement('Invoice');
        $newNode = $dom->appendChild($invoice);

        $newNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $newNode->setAttribute('xmlns:xsd','http://www.w3.org/2001/XMLSchema');
        $newNode->setAttribute('xmlns:cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $newNode->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $newNode->setAttribute('xmlns:ccts', 'urn:un:unece:uncefact:documentation:2');
        $newNode->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $newNode->setAttribute('xmlns:ext','urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $newNode->setAttribute('xmlns:qdt', 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2');
        $newNode->setAttribute('xmlns:udt','urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2');
        $newNode->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2'); 
        
        $ublExtensions = $dom->createElement('ext:UBLExtensions');
        $invoice->appendChild($ublExtensions);

        $ublExtension = $dom->createElement('ext:UBLExtension');
        $ublExtensions->appendChild($ublExtension);

        $extensionContent = $dom->createElement('ext:ExtensionContent');
        $ublExtension->appendChild($extensionContent);

        ////////////////////////////////////77

        $ublVersionID = $dom->createElement('cbc:UBLVersionID');
        $ublVersionID->nodeValue = '2.1';
        $invoice->appendChild($ublVersionID);

        $customizationID = $dom->createElement('cbc:CustomizationID');
        // $schemeAgencyName =$dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='PE:SUNAT';
        // $customizationID->appendChild($schemeAgencyName);
        $customizationID->nodeValue = '2.0';
        $invoice->appendChild($customizationID);

        #VARIABLE
        // $profileID = $dom->createElement('cbc:ProfileID');
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Tipo de Operación';
        // $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='PE:SUNAT';
        // $schemeURI = $dom->createAttribute('schemeURI');
        // $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51';
        // $profileID->appendChild($schemeName);
        // $profileID->appendChild($schemeAgencyName);
        // $profileID->appendChild($schemeURI);
        // $profileID->nodeValue='0101';
        // $invoice->appendChild($profileID);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $invoice->appendChild($cbcID);

        #VARIABLE
        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = $fecha;
        $invoice->appendChild($issueDate);

        #VARIABLE
        $issueTime = $dom->createElement('cbc:IssueTime');
        $issueTime->nodeValue = $hora;
        $invoice->appendChild($issueTime);

        #VARIABLE
        $dueDate = $dom->createElement('cbc:DueDate');
        $dueDate->nodeValue = $fecha;
        $invoice->appendChild($dueDate);

        #VARIABLE
        $invoiceTypeCode = $dom->createElement('cbc:InvoiceTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName= $dom->createAttribute('listName');
        $listName->value='Tipo de Documento';
        $listURI = $dom->createAttribute('listURI');
        $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01';
        $listID = $dom->createAttribute('listID');
        $listID->value='0101';
        $name = $dom->createAttribute('name');
        $name->value='Tipo de Operacion';
        $listSchemeURI = $dom->createAttribute('listSchemeURI');
        $listSchemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo51';
        $invoiceTypeCode->appendChild($listAgencyName);
        $invoiceTypeCode->appendChild($listName);
        $invoiceTypeCode->appendChild($listURI);
        $invoiceTypeCode->appendChild($listID);
        $invoiceTypeCode->appendChild($name);
        $invoiceTypeCode->appendChild($listSchemeURI);
        $invoiceTypeCode->nodeValue = $tipo_comprobante;
        $invoice->appendChild($invoiceTypeCode);

        #VARIABLE
        $note = $dom->createElement('cbc:Note');
        $languageLocaleID = $dom->createAttribute('languageLocaleID');
        $languageLocaleID->value='1000';
        $note->appendChild($languageLocaleID);
        // $note->nodeValue='SEISCIENTOS TREINTA Y DOS CON 50/ 100';
        $note->nodeValue=$leyenda.' SOLES';
        $invoice->appendChild($note);
        
        #VARIABLE
        $documentCurrencyCode = $dom->createElement('cbc:DocumentCurrencyCode');
        $listID = $dom->createAttribute('listID');
        $listID->value='ISO 4217 Alpha';
        $listName =$dom->createAttribute('listName');
        $listName->value='Currency';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='United Nations Economic Commission for Europe';
        $documentCurrencyCode->appendChild($listID);
        $documentCurrencyCode->appendChild($listName);
        $documentCurrencyCode->appendChild($listAgencyName);
        $documentCurrencyCode->nodeValue = 'PEN';
        $invoice->appendChild($documentCurrencyCode);

        #VARIABLE
        $lineCountNumeric = $dom->createElement('cbc:LineCountNumeric');
        $lineCountNumeric->nodeValue=count($idarticulo);
        $invoice->appendChild($lineCountNumeric);

        #VARIABLE
        // $orderReference = $dom->createElement('cac:OrderReference');
        // $invoice->appendChild($orderReference);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue='0004-00028955';
        // $orderReference->appendChild($cbcID);

        #VARIABLE
        // $despatchDocumentReference = $dom->createElement('cac:DespatchDocumentReference');
        // $invoice->appendChild($despatchDocumentReference);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue='0003-00051304';
        // $despatchDocumentReference->appendChild($cbcID);        

        // $issueDate = $dom->createElement('cbc:IssueDate');
        // $issueDate->nodeValue = '2018-04-02';
        // $despatchDocumentReference->appendChild($issueDate);

        // $documentTypeCode = $dom->createElement('cbc:DocumentTypeCode');
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listName =$dom->createAttribute('listName');
        // $listName->value='Tipo de Documento';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01';
        // $documentTypeCode->appendChild($listAgencyName);
        // $documentTypeCode->appendChild($listName);
        // $documentTypeCode->appendChild($listURI);
        // $documentTypeCode->nodeValue = '09';
        // $despatchDocumentReference->appendChild($documentTypeCode);

        $signature = $dom->createElement('cac:Signature');
        $invoice->appendChild($signature);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = 'IDSignSP';
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $signature->appendChild($cbcID);

        $signatoryParty = $dom->createElement('cac:SignatoryParty');
        $signature->appendChild($signatoryParty);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $signatoryParty->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $signatoryParty->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = $empresa->razon_social;
        $partyName->appendChild($cbcName);

        $digitalSignatoreAttachment = $dom->createElement('cac:DigitalSignatureAttachment');
        $signature->appendChild($digitalSignatoreAttachment);

        $externalReference = $dom->createElement('cac:ExternalReference');
        $digitalSignatoreAttachment->appendChild($externalReference);

        #VARIABLE
        $cbcURI = $dom->createElement('cbc:URI');
        $cbcURI->nodeValue = '#'.$serie_comprobante.'-'.$num_comprobante;
        $externalReference->appendChild($cbcURI);

        $accountSupplierParty = $dom->createElement('cac:AccountingSupplierParty');
        $invoice->appendChild($accountSupplierParty);

        $party = $dom->createElement('cac:Party');
        $accountSupplierParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $acbcName = $dom->createCDATASection($empresa->nombre_comercial);
        // $cbcName->nodeValue='CORPORACION BJR PRUEBA';
        $cbcName->appendChild($acbcName);
        $partyName->appendChild($cbcName);

        $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        $party->appendChild($partyTaxScheme);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aRegistrationName);
        $partyTaxScheme->appendChild($registrationName);

        #VARIABLE
        $companyID = $dom->createElement('cbc:CompanyID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $companyID->appendChild($schemeID);
        $companyID->appendChild($schemeName);
        $companyID->appendChild($schemeAgencyName);
        $companyID->appendChild($schemeURI);
        $companyID->nodeValue = $empresa->ruc;
        $partyTaxScheme->appendChild($companyID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $partyTaxScheme->appendChild($taxScheme);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value = '6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $empresa->ruc;
        $taxScheme->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aRegistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $registrationAddress = $dom->createElement('cac:RegistrationAddress');
        $partyLegalEntity->appendChild($registrationAddress);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Ubigeos';
        $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:INEI';
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $registrationAddress->appendChild($cbcID);

        #VARIABLE
        $addressTypeCode = $dom->createElement('cbc:AddressTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName = $dom->createAttribute('listName');
        $listName->value='Establecimientos anexos';
        $addressTypeCode->appendChild($listAgencyName);
        $addressTypeCode->appendChild($listName);
        $addressTypeCode->nodeValue='0000';
        $registrationAddress->appendChild($addressTypeCode);

        #VARIABLE
        $cityName = $dom->createElement('cbc:CityName');
        $acityName = $dom->createCDATASection($empresa->departamento);
        $cityName->appendChild($acityName);
        $registrationAddress->appendChild($cityName);

        #VARIABLE
        $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        $acountrySubentity = $dom->createCDATASection($empresa->provincia);
        $countrySubentity->appendChild($acountrySubentity);
        $registrationAddress->appendChild($countrySubentity);

        #VARIABLE
        $district = $dom->createElement('cbc:District');
        $adistrict = $dom->createCDATASection($empresa->distrito);
        $district->appendChild($adistrict);
        $registrationAddress->appendChild($district);

        $addressLine = $dom->createElement('cac:AddressLine');
        $registrationAddress->appendChild($addressLine);

        #VARIABLE
        $line = $dom->createElement('cbc:Line');
        $aline = $dom->createCDATASection($empresa->direccion);
        $line->appendChild($aline);
        $addressLine->appendChild($line);

        $country = $dom->createElement('cac:Country');
        $registrationAddress->appendChild($country);

        $identificationCode = $dom->createElement('cbc:IdentificationCode');
        $listID = $dom->createAttribute('listID');
        $listID->value='ISO 3166-1';
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='United Nations Economic Commission for Europe';
        $listName = $dom->createAttribute('listName');
        $listName->value='Country';
        $identificationCode->appendChild($listID);
        $identificationCode->appendChild($listAgencyName);
        $identificationCode->appendChild($listName);
        $identificationCode->nodeValue='PE';
        $country->appendChild($identificationCode);

        // $contact = $dom->createElement('cac:Contact');
        // $party->appendChild($contact);

        // $name = $dom->createElement('cbc:Name');
        // $name->nodeValue='MIGUEL DELGADO';
        // $contact->appendChild($name);

        $query = Persona::findOrFail($idcliente);

        $accountCustomerParty = $dom->createElement('cac:AccountingCustomerParty');
        $invoice->appendChild($accountCustomerParty);

        $party = $dom->createElement('cac:Party');
        $accountCustomerParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Documento de Identidad';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue = $query->num_documento;
        $partyIdentification->appendChild($cbcID);

        // $partyName = $dom->createElement('cac:PartyName');
        // $party->appendChild($partyName);

        // #VARIABLE
        // $cbcName = $dom->createElement('cbc:Name');
        // $aCbcName = $dom->createCDATASection($query->nombre);
        // $cbcName->appendChild($aCbcName);
        // $partyName->appendChild($cbcName);

        // $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        // $party->appendChild($partyTaxScheme);

        // #VARIABLE
        // $registrationName = $dom->createElement('cbc:RegistrationName');
        // $registrationName->appendChild($dom->createCDATASection($query->nombre));
        // $partyTaxScheme->appendChild($registrationName);

        // #VARIABLE
        // $companyID = $dom->createElement('cbc:CompanyID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value = '6';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='PE:SUNAT';
        // $schemeURI = $dom->createAttribute('schemeURI');
        // $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        // $companyID->appendChild($schemeID);
        // $companyID->appendChild($schemeName);
        // $companyID->appendChild($schemeAgencyName);
        // $companyID->appendChild($schemeURI);
        // $companyID->nodeValue = $query->num_documento;
        // $partyTaxScheme->appendChild($companyID);

        // $taxScheme = $dom->createElement('cac:TaxScheme');
        // $partyTaxScheme->appendChild($taxScheme);

        // #VARIABLE
        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value = '6';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='PE:SUNAT';
        // $schemeURI = $dom->createAttribute('schemeURI');
        // $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        // $cbcID->appendChild($schemeID);
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // $cbcID->appendChild($schemeURI);
        // $cbcID->nodeValue = $query->num_documento;
        // $taxScheme->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection($query->nombre);
        $registrationName->appendChild($aRegistrationName);
        $partyLegalEntity->appendChild($registrationName);

        // $registrationAddress = $dom->createElement('cac:RegistrationAddress');
        // $partyLegalEntity->appendChild($registrationAddress);

        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Ubigeos';
        // $schemeAgencyName=$dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='PE:INEI';
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // // $cbcID->nodeValue='010701';
        // $registrationAddress->appendChild($cbcID);

        // $cityName = $dom->createElement('cbc:CityName');
        // $acityName = $dom->createCDATASection('');
        // $cityName->appendChild($acityName);
        // $registrationAddress->appendChild($cityName);

        // $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        // $acountrySubentity = $dom->createCDATASection('');
        // $countrySubentity->appendChild($acountrySubentity);
        // $registrationAddress->appendChild($countrySubentity);

        // $district = $dom->createElement('cbc:District');
        // $adistrict = $dom->createCDATASection('');
        // $district->appendChild($adistrict);
        // $registrationAddress->appendChild($district);


        // $addressLine = $dom->createElement('cac:AddressLine');
        // $registrationAddress->appendChild($addressLine);

        // #VARIABLE
        // $line = $dom->createElement('cbc:Line');
        // $aline = $dom->createCDATASection($query->direccion);
        // $line->appendChild($aline);
        // $addressLine->appendChild($line);

        // $country = $dom->createElement('cac:Country');
        // $registrationAddress->appendChild($country);

        // $identificationCode = $dom->createElement('cbc:IdentificationCode');
        // $listID = $dom->createAttribute('listID');
        // $listID->value='ISO 3166-1';
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='United Nations Economic Commission for Europe';
        // $listName = $dom->createAttribute('listName');
        // $listName->value='Country';
        // $identificationCode->appendChild($listID);
        // $identificationCode->appendChild($listAgencyName);
        // $identificationCode->appendChild($listName);
        // $identificationCode->nodeValue='PE';
        // $country->appendChild($identificationCode);

        // $allowanceCharge = $dom->createElement('cac:AllowanceCharge');
        // $invoice->appendChild($allowanceCharge);

        // $chargeIndicator = $dom->createElement('cbc:ChargeIndicator');
        // $chargeIndicator->nodeValue='false';
        // $allowanceCharge->appendChild($chargeIndicator);

        // $allowanceChargeReasonCode = $dom->createElement('cbc:AllowanceChargeReasonCode');
        // $listName = $dom->createAttribute('listName');
        // $listName->value='Cargo/descuento';
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo53';
        // $allowanceChargeReasonCode->appendChild($listName);
        // $allowanceChargeReasonCode->appendChild($listAgencyName);
        // $allowanceChargeReasonCode->appendChild($listURI);
        // $allowanceChargeReasonCode->nodeValue = '02';
        // $allowanceCharge->appendChild($allowanceChargeReasonCode);

        // $multiplierFactorNumeric = $dom->createElement('cbc:MultiplierFactorNumeric');
        // $multiplierFactorNumeric->nodeValue = '0.00';
        // $allowanceCharge->appendChild($multiplierFactorNumeric);

        // $amount = $dom->createElement('cbc:Amount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $amount->appendChild($currencyID);
        // $amount->nodeValue='0.00';
        // $allowanceCharge->appendChild($amount);

        // $baseAmount = $dom->createElement('cbc:BaseAmount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $baseAmount->appendChild($currencyID);
        // $baseAmount->nodeValue='0.00';
        // $allowanceCharge->appendChild($baseAmount);


        $neto = round(($total_venta/1.18),2);
        $igv = $total_venta-$neto;

        $taxTotal = $dom->createElement('cac:TaxTotal');
        $invoice->appendChild($taxTotal);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxTotal->appendChild($taxAmount);

        //////////////////////////////////////////////////////

        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        $taxTotal->appendChild($taxSubTotal);

        #VARIABLE
        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $taxableAmount->appendChild($currencyID);
        $taxableAmount->nodeValue=$neto;
        $taxSubTotal->appendChild($taxableAmount);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxSubTotal->appendChild($taxAmount);

        $taxCategory = $dom->createElement('cac:TaxCategory');
        $taxSubTotal->appendChild($taxCategory);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='UN/ECE 5305';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='Tax Category Identifier';
        $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemeAgencyName->value='United Nations Economic Commission for Europe';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemeAgencyName);
        $cbcID->nodeValue = 'S';
        $taxCategory->appendChild($cbcID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $taxCategory->appendChild($taxScheme);

        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='UN/ECE 5153';
        $schemeAgencyID = $dom->createAttribute('schemeAgencyID');
        $schemeAgencyID->value='6';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeAgencyID);
        $cbcID->nodeValue = '1000';
        $taxScheme->appendChild($cbcID);

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = 'IGV';
        $taxScheme->appendChild($cbcName);

        $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
        $taxTypeCode->nodeValue = 'VAT';
        $taxScheme->appendChild($taxTypeCode);

        $legalMonetaryTotal = $dom->createElement('cac:LegalMonetaryTotal');
        $invoice->appendChild($legalMonetaryTotal);

        #VARIABLE
        // $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $lineExtensionAmount->appendChild($currencyID);
        // $lineExtensionAmount->nodeValue=$neto;
        // $legalMonetaryTotal->appendChild($lineExtensionAmount);

        // #VARIABLE
        // $taxInclusiveAmount = $dom->createElement('cbc:TaxInclusiveAmount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $taxInclusiveAmount->appendChild($currencyID);
        // $taxInclusiveAmount->nodeValue=number_format(round($total_venta,2),2,'.','');
        // $legalMonetaryTotal->appendChild($taxInclusiveAmount);

        // #VARIABLE
        // $allowanceTotalAmount = $dom->createElement('cbc:AllowanceTotalAmount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $allowanceTotalAmount->appendChild($currencyID);
        // $allowanceTotalAmount->nodeValue='0.00';
        // $legalMonetaryTotal->appendChild($allowanceTotalAmount);

        // #VARIABLE
        // $chargeTotalAmount = $dom->createElement('cbc:ChargeTotalAmount');
        // $currencyID = $dom->createAttribute('currencyID');
        // $currencyID->value='PEN';
        // $chargeTotalAmount->appendChild($currencyID);
        // $chargeTotalAmount->nodeValue='0.00';
        // $legalMonetaryTotal->appendChild($chargeTotalAmount);

        #VARIABLE
        $payableAmount = $dom->createElement('cbc:PayableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $payableAmount->appendChild($currencyID);
        $payableAmount->nodeValue=number_format(round($total_venta,2),2,'.','');
        $legalMonetaryTotal->appendChild($payableAmount);

        //INVOICE LINE 1

        $cont = 1;
        while($cont <= count($idarticulo)){
            $totalItem = $cantidad[$cont-1]*$precio_venta[$cont-1];

            $neto_item = $totalItem/1.18;
            $impuesto = $totalItem-$neto_item;

            $invoiceLine = $dom->createElement('cac:InvoiceLine');
            $invoice->appendChild($invoiceLine);

            #VARIABLE
            $cbcID = $dom->createElement('cbc:ID');
            $cbcID->nodeValue = $cont;
            $invoiceLine->appendChild($cbcID);

            #VARIABLE
            $invoicedQuantity = $dom->createElement('cbc:InvoicedQuantity');
            $aInvoicedQuantity = $dom->createAttribute('unitCode');
            $aInvoicedQuantity->value = 'NIU';
            $unitCodeList = $dom->createAttribute('unitCodeListID');
            $unitCodeList->value='UN/ECE rec 20';
            $unitCodeListAgencyName = $dom->createAttribute('unitCodeListAgencyName');
            $unitCodeListAgencyName->value='United Nations Economic Commission for Europe';
            $invoicedQuantity->appendChild($aInvoicedQuantity);
            $invoicedQuantity->appendChild($unitCodeList);
            $invoicedQuantity->appendChild($unitCodeListAgencyName);
            $invoicedQuantity->nodeValue = $cantidad[$cont-1];
            $invoiceLine->appendChild($invoicedQuantity);

            #VARIABLE
            $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
            $aLineExtensionAmount = $dom->createAttribute('currencyID');
            $aLineExtensionAmount->value = 'PEN';
            $lineExtensionAmount->appendChild($aLineExtensionAmount);
            $lineExtensionAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
            $invoiceLine->appendChild($lineExtensionAmount);

            $pricingReference = $dom->createElement('cac:PricingReference');
            $invoiceLine->appendChild($pricingReference);

            $alternativeConditionPrice = $dom->createElement('cac:AlternativeConditionPrice');
            $pricingReference->appendChild($alternativeConditionPrice);

            #VARIABLE
            $priceAmount = $dom->createElement('cbc:PriceAmount');
            $aPriceAmount = $dom->createAttribute('currencyID');
            $aPriceAmount->value = 'PEN';
            $priceAmount->appendChild($aPriceAmount);
            $priceAmount->nodeValue = number_format(round($precio_venta[$cont-1],2),2,'.','');
            $alternativeConditionPrice->appendChild($priceAmount);

            #VARIABLE
            $priceTypeCode = $dom->createElement('cbc:PriceTypeCode');
            $listName = $dom->createAttribute('listName');
            $listName->value='Tipo de Precio';
            $listAgencyName = $dom->createAttribute('listAgencyName');
            $listAgencyName->value='PE:SUNAT';
            $listURI = $dom->createAttribute('listURI');
            $listURI->value = 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16';
            $priceTypeCode->appendChild($listName);
            $priceTypeCode->appendChild($listAgencyName);
            $priceTypeCode->appendChild($listURI);
            $priceTypeCode->nodeValue = '01';
            $alternativeConditionPrice->appendChild($priceTypeCode);

            $taxTotal = $dom->createElement('cac:TaxTotal');
            $invoiceLine->appendChild($taxTotal);

            #VARIABLE
            $taxAmount = $dom->createElement('cbc:TaxAmount');
            $aTaxAmount = $dom->createAttribute('currencyID');
            $aTaxAmount->value='PEN';
            $taxAmount->appendChild($aTaxAmount);
            $taxAmount->nodeValue = number_format(round($impuesto,2),2,'.','');
            $taxTotal->appendChild($taxAmount);

            $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
            $taxTotal->appendChild($taxSubTotal);

            #VARIABLE
            $taxableAmount = $dom->createElement('cbc:TaxableAmount');
            $aTaxAmount = $dom->createAttribute('currencyID');
            $aTaxAmount->value='PEN';
            $taxableAmount->appendChild($aTaxAmount);
            $taxableAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
            $taxSubTotal->appendChild($taxableAmount);

            #VARIABLE
            $taxAmount = $dom->createElement('cbc:TaxAmount');
            $aTaxAmount = $dom->createAttribute('currencyID');
            $aTaxAmount->value='PEN';
            $taxAmount->appendChild($aTaxAmount);
            $taxAmount->nodeValue = number_format(round($impuesto,2),2,'.','');
            $taxSubTotal->appendChild($taxAmount);

            $taxCategory = $dom->createElement('cac:TaxCategory');
            $taxSubTotal->appendChild($taxCategory);

            #VARIABLE
            $cbcID = $dom->createElement('cbc:ID');
            $schemeID = $dom->createAttribute('schemeID');
            $schemeID->value='UN/ECE 5305';
            $schemeName = $dom->createAttribute('schemeName');
            $schemeName->value='Tax Category Identifier';
            $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
            $schemeAgencyName->value='United Nations Economic Commission for Europe';
            $cbcID->appendChild($schemeID);
            $cbcID->appendChild($schemeName);
            $cbcID->appendChild($schemeAgencyName);
            $cbcID->nodeValue = 'S';
            $taxCategory->appendChild($cbcID);

            $cbcPercent = $dom->createElement('cbc:Percent');
            $cbcPercent->nodeValue='18.00';
            $taxCategory->appendChild($cbcPercent);

            #VARIABLE
            $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
            $listAgencyName = $dom->createAttribute('listAgencyName');
            $listAgencyName->value='PE:SUNAT';
            $listName = $dom->createAttribute('listName');
            $listName->value='SUNAT:Codigo de Tipo de Afectación del IGV';
            $listURI = $dom->createAttribute('listURI');
            $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07';
            $taxExemptionReasonCode->appendChild($listAgencyName);
            $taxExemptionReasonCode->appendChild($listName);
            $taxExemptionReasonCode->appendChild($listURI);
            $taxExemptionReasonCode->nodeValue = '10';
            $taxCategory->appendChild($taxExemptionReasonCode);

            $taxScheme = $dom->createElement('cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $cbcID = $dom->createElement('cbc:ID');
            $schemeID = $dom->createAttribute('schemeID');
            $schemeID->value='UN/ECE 5153';
            $schemeName = $dom->createAttribute('schemeName');
            $schemeName->value='Tax Scheme Identifier';
            $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
            $schemeAgencyName->value='United Nations Economic Commission for Europe';
            $cbcID->appendChild($schemeID);
            $cbcID->appendChild($schemeName);
            $cbcID->appendChild($schemeAgencyName);
            $cbcID->nodeValue = '1000';
            $taxScheme->appendChild($cbcID);

            $cbcName = $dom->createElement('cbc:Name');
            $cbcName->nodeValue = 'IGV';
            $taxScheme->appendChild($cbcName);

            $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
            $taxTypeCode->nodeValue = 'VAT';
            $taxScheme->appendChild($taxTypeCode);

            $item = $dom->createElement('cac:Item');
            $invoiceLine->appendChild($item);

            $articulo = Articulo::findOrFail($idarticulo[$cont-1]);

            #VARIABLE
            $description = $dom->createElement('cbc:Description');
            $adescription = $dom->createCDATASection($articulo->nombre);
            $description->appendChild($adescription);
            // $description->nodeValue = $articulo->nombre;
            $item->appendChild($description);

            // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
            // $item->appendChild($sellersItemIdentification);

            // $cbcID = $dom->createElement('cbc:ID');
            // $cbcID->nodeValue = 'CBJI300-18-BT';
            // $sellersItemIdentification->appendChild($cbcID);

            $price = $dom->createElement('cac:Price');
            $invoiceLine->appendChild($price);

            $priceAmount = $dom->createElement('cbc:PriceAmount');
            $aPriceAmount = $dom->createAttribute('currencyID');
            $aPriceAmount->value = 'PEN';
            $priceAmount->appendChild($aPriceAmount);
            $priceAmount->nodeValue= number_format(round($precio_venta[$cont-1],2),2,'.','');
            $price->appendChild($priceAmount);

            $cont++;
        }

        

        //INVOICE LINE 2

        // $invoiceLine = $dom->createElement('cac:InvoiceLine');
        // $invoice->appendChild($invoiceLine);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = '2';
        // $invoiceLine->appendChild($cbcID);

        // $invoicedQuantity = $dom->createElement('cbc:InvoicedQuantity');
        // $aInvoicedQuantity = $dom->createAttribute('unitCode');
        // $aInvoicedQuantity->value = 'NIU';
        // $unitCodeList = $dom->createAttribute('unitCodeListID');
        // $unitCodeList->value='UN/ECE rec 20';
        // $unitCodeListAgencyName = $dom->createAttribute('unitCodeListAgencyName');
        // $unitCodeListAgencyName->value='United Nations Economic Commission for Europe';
        // $invoicedQuantity->appendChild($aInvoicedQuantity);
        // $invoicedQuantity->appendChild($unitCodeList);
        // $invoicedQuantity->appendChild($unitCodeListAgencyName);
        // $invoicedQuantity->nodeValue = '20.0000';
        // $invoiceLine->appendChild($invoicedQuantity);

        // $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
        // $aLineExtensionAmount = $dom->createAttribute('currencyID');
        // $aLineExtensionAmount->value = 'PEN';
        // $lineExtensionAmount->appendChild($aLineExtensionAmount);
        // $lineExtensionAmount->nodeValue = '398.31';
        // $invoiceLine->appendChild($lineExtensionAmount);

        // $pricingReference = $dom->createElement('cac:PricingReference');
        // $invoiceLine->appendChild($pricingReference);

        // $alternativeConditionPrice = $dom->createElement('cac:AlternativeConditionPrice');
        // $pricingReference->appendChild($alternativeConditionPrice);

        // $priceAmount = $dom->createElement('cbc:PriceAmount');
        // $aPriceAmount = $dom->createAttribute('currencyID');
        // $aPriceAmount->value = 'PEN';
        // $priceAmount->appendChild($aPriceAmount);
        // $priceAmount->nodeValue = '23.50';
        // $alternativeConditionPrice->appendChild($priceAmount);

        // $priceTypeCode = $dom->createElement('cbc:PriceTypeCode');
        // $listName = $dom->createAttribute('listName');
        // $listName->value='Tipo de Precio';
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value = 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo16';
        // $priceTypeCode->appendChild($listName);
        // $priceTypeCode->appendChild($listAgencyName);
        // $priceTypeCode->appendChild($listURI);
        // $priceTypeCode->nodeValue = '01';
        // $alternativeConditionPrice->appendChild($priceTypeCode);

        // $taxTotal = $dom->createElement('cac:TaxTotal');
        // $invoiceLine->appendChild($taxTotal);

        // $taxAmount = $dom->createElement('cbc:TaxAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxAmount->appendChild($aTaxAmount);
        // $taxAmount->nodeValue = '71.70';
        // $taxTotal->appendChild($taxAmount);

        // $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        // $taxTotal->appendChild($taxSubTotal);

        // $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxableAmount->appendChild($aTaxAmount);
        // $taxableAmount->nodeValue = '398.31';
        // $taxSubTotal->appendChild($taxableAmount);

        // $taxAmount = $dom->createElement('cbc:TaxAmount');
        // $aTaxAmount = $dom->createAttribute('currencyID');
        // $aTaxAmount->value='PEN';
        // $taxAmount->appendChild($aTaxAmount);
        // $taxAmount->nodeValue = '71.70';
        // $taxSubTotal->appendChild($taxAmount);

        // $taxCategory = $dom->createElement('cac:TaxCategory');
        // $taxSubTotal->appendChild($taxCategory);

        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value='UN/ECE 5305';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Tax Category Identifier';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='United Nations Economic Commission for Europe';
        // $cbcID->appendChild($schemeID);
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // $cbcID->nodeValue = 'S';
        // $taxCategory->appendChild($cbcID);

        // $cbcPercent = $dom->createElement('cbc:Percent');
        // $cbcPercent->nodeValue='18.00';
        // $taxCategory->appendChild($cbcPercent);

        // $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
        // $listAgencyName = $dom->createAttribute('listAgencyName');
        // $listAgencyName->value='PE:SUNAT';
        // $listName = $dom->createAttribute('listName');
        // $listName->value='SUNAT:Codigo de Tipo de Afectación del IGV';
        // $listURI = $dom->createAttribute('listURI');
        // $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo07';
        // $taxExemptionReasonCode->appendChild($listAgencyName);
        // $taxExemptionReasonCode->appendChild($listName);
        // $taxExemptionReasonCode->appendChild($listURI);
        // $taxExemptionReasonCode->nodeValue = '10';
        // $taxCategory->appendChild($taxExemptionReasonCode);

        // $taxScheme = $dom->createElement('cac:TaxScheme');
        // $taxCategory->appendChild($taxScheme);

        // $cbcID = $dom->createElement('cbc:ID');
        // $schemeID = $dom->createAttribute('schemeID');
        // $schemeID->value='UN/ECE 5153';
        // $schemeName = $dom->createAttribute('schemeName');
        // $schemeName->value='Tax Scheme Identifier';
        // $schemeAgencyName = $dom->createAttribute('schemeAgencyName');
        // $schemeAgencyName->value='United Nations Economic Commission for Europe';
        // $cbcID->appendChild($schemeID);
        // $cbcID->appendChild($schemeName);
        // $cbcID->appendChild($schemeAgencyName);
        // $cbcID->nodeValue = '1000';
        // $taxScheme->appendChild($cbcID);

        // $cbcName = $dom->createElement('cbc:Name');
        // $cbcName->nodeValue = 'IGV';
        // $taxScheme->appendChild($cbcName);

        // $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
        // $taxTypeCode->nodeValue = 'VAT';
        // $taxScheme->appendChild($taxTypeCode);

        // $item = $dom->createElement('cac:Item');
        // $invoiceLine->appendChild($item);

        // $description = $dom->createElement('cbc:Description');
        // $description->nodeValue = 'LLANTA 350-8 SU-037 4PR|';
        // $item->appendChild($description);

        // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
        // $item->appendChild($sellersItemIdentification);

        // $cbcID = $dom->createElement('cbc:ID');
        // $cbcID->nodeValue = 'SU350-8-037-4PR';
        // $sellersItemIdentification->appendChild($cbcID);

        // $price = $dom->createElement('cac:Price');
        // $invoiceLine->appendChild($price);

        // $priceAmount = $dom->createElement('cbc:PriceAmount');
        // $aPriceAmount = $dom->createAttribute('currencyID');
        // $aPriceAmount->value = 'PEN';
        // $priceAmount->appendChild($aPriceAmount);
        // $priceAmount->nodeValue='19.92';
        // $price->appendChild($priceAmount);






        // $xmlName = sprintf('%s-%s-%s', "Prueba", '01',1);
        $xmlName = $empresa->ruc."-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
        $xmlPath = public_path().'\cdn/xml/'. $xmlName . '.XML';
        $xmlFullPath = $xmlPath;
        file_exists($xmlFullPath) ? unlink($xmlFullPath) : '';
        \File::put($xmlFullPath, $dom->saveXML());
        chmod($xmlFullPath, 0777);


        // $privateKey = storage_path('sunat/keys/LLAVE_PRIVADA.pem');
        // $publicKey = storage_path('sunat/keys/LLAVE_PUBLICA.pem');
        // if (!file_exists($privateKey))
        //     throw new Exception('No se encuentra la LLAVE PRIVADA');
        // if (!file_exists($publicKey))
        //     throw new Exception('No se encuentra la LLAVE PUBLICA');                    

        // $cmdString = sprintf('xmlsec1 --sign --privkey-pem %s,%s --output %s %s', $privateKey, $publicKey,
        //     $xmlFullPath, $xmlFullPath);
        // exec($cmdString);

        // while (!file_exists($xmlFullPath)) {
        //     sleep(1);
        //     if (file_exists($xmlFullPath)) {
        //         break;
        //     }
        // }
        // chmod($xmlFullPath, 0777);


        //SEGUNDA FORMA DE FIRMAR UN DOCUMENTO 

        // $privateKey = storage_path('sunat/keys/PrivateKey.key');
        // $publicKey = storage_path('sunat/keys/ServerCertificate.cer');

        $privateKey = storage_path('sunat/keys/ros.key');
        $publicKey = storage_path('sunat/keys/ros.cer');

        if (!file_exists($privateKey))
            throw new Exception('No se encuentra la LLAVE PRIVADA');
        if (!file_exists($publicKey))
            throw new Exception('No se encuentra la LLAVE PUBLICA');

        $ReferenceNodeName = 'ExtensionContent';

        // Load the XML to be signed
        $doc = new DOMDocument();
        $doc->load($xmlFullPath);

        // Create a new Security object
        $objDSig = new XMLSecurityDSig();
        // Use the c14n exclusive canonicalization
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc, 
            XMLSecurityDSig::SHA1, 
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
            $options = array('force_uri' => true)
        );

        // Create a new (private) Security key
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
        /*
        If key has a passphrase, set it using
        $objKey->passphrase = '<passphrase>';
        */

        // Load the private key
        $objKey->loadKey($privateKey, TRUE);
        //$objKey->loadKey('certificates/PrivateKey.key', TRUE);

        // Sign the XML file
        $objDSig->sign($objKey,$doc->getElementsByTagName($ReferenceNodeName)->item(0));

        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($publicKey));
        //$objDSig->add509Cert(file_get_contents('certificates/ServerCertificate.cer'));
        // Append the signature to the XML
        //die(var_dump($doc->documentElement));
        $objDSig->appendSignature($doc->getElementsByTagName($ReferenceNodeName)->item(0));
        //$objDSig->appendSignature($ReferenceNodeName);
        // Save the signed XML
        $doc->save($xmlFullPath);

        /////////////
        

        $zipPath = 'cdn/document/prueba21' . DIRECTORY_SEPARATOR . $xmlName . '.ZIP';
        $zipFullPath = public_path($zipPath);

        file_exists($zipFullPath) ? unlink($zipFullPath) : '';
            \Zipper::make($zipFullPath)->add($xmlFullPath)->close();
            unlink($xmlFullPath);
    }


    public function crearPDF($empresa,$cliente,$items, $leyenda,$firma){
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false,false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetTopMargin(63.23889);
        $pdf->SetLeftMargin(5);
        $pdf->SetRightMargin(5);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetCellPadding(0.5);

        # Logo
        // $logo = public_path('/static/images/logo_pdf_a4_default.jpg');
        $logo = public_path('/static/images/ESERSEC.png');
        if (file_exists(public_path('/static/images/logo_pdf_a4.jpg'))) {
            $logo = public_path('/static/images/logo_pdf_a4.jpg');
        }
        $pdf->SetCellPadding(0.7);
        $pdf->Image($logo, 5, 5, 47.625, 22.49);

        $pdf->SetXY(58, 5);
        $x = $pdf->GetX();
        $pdf->SetFont('helvetica', '', 8);

        # Breve descripción de la empresa emisora
        $rhm = $pdf->getStringHeight(0, 'string');
        $pdf->MultiCell(74, 0, $empresa->razon_social, 'L', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);
        $pdf->SetX($x);
        $pdf->MultiCell(74, 0, $empresa->direccion, 'L', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);
        $pdf->SetX($x);
        $pdf->MultiCell(74, 0, $empresa->distrito.' '.$empresa->provincia.' '.$empresa->departamento.' PERU', 'L', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);

        $pdf->SetX($x);
        $pdf->MultiCell(74, 4, 'Tel.: ' . $empresa->telefono, 'L', 'L');
         
        $pdf->SetX($x);
        $pdf->MultiCell(74, 4, 'E-mail: ' . $empresa->correo, 'L', 'L');
        
        # Descripción del documento
        $pdf->SetXY(140, 5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(65, 7, 'R.U.C. ' . $empresa->ruc, 'LTR', 'C', false, 1, '', '', true, 0, false,
            true, 7, 'M');
        $pdf->Ln(0);
        $pdf->SetX(140);
        $pdf->SetFont('helvetica', '', 12);
        if($cliente->tipo_comprobante=='01'){
            $pdf->MultiCell(65, 8, 'FACTURA ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        }else{
            $pdf->MultiCell(65, 8, 'BOLETA ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        }
        $pdf->Ln(0);
        $pdf->SetX(140);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(65, 7, $cliente->serie_comprobante . ' Nº ' . $cliente->num_comprobante, 'LRB', 'C', false, 1, '',
            '', true, 0, false, true, 7, 'M');

        # Detalle del cliente
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(5, 29);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(27, 4, 'SEÑOR (TITULAR)', 1, 'L', true, 1, '', '', true, 0, false, false, $rhm);
        $pdf->SetXY(32, $y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 4, $cliente->nombre, 'TR', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);
        $pdf->SetXY(5, 33);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(27, 4, 'DIRECCIÓN', 1, 'L', true);
        $pdf->SetXY(32, $y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 4, $cliente->direccion, 'R', 'L', false, 1, '', '', true, 0, false, false,$rhm);
        $pdf->SetXY(5, 37);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $documentoTipo = '';
        switch ('6') :
            case '1':
                $documentoTipo = 'DNI';
                break;
            case '6':
                $documentoTipo = 'RUC';
                break;
        endswitch;
        $pdf->MultiCell(27, 4, $documentoTipo, 1, 'L', true);
        $pdf->SetXY(32, $y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 4, $cliente->num_documento, 'RB', 'L');
        
        # Detalle adicional del documento
        $pdf->SetXY(140, 29);
        $y = $pdf->GetY();
        $pdf->MultiCell(32, 4, 'TIPO DE MONEDA', 'TL', 'L');
        $pdf->SetXY(172, $y);
        $pdf->MultiCell(10, 4, ':', 'T', 'C');
        $pdf->SetXY(182, $y);
        $pdf->MultiCell(23, 4, 'NUEVO SOL', 'TR', 'L');
        $pdf->SetXY(140, 33);
        $y = $pdf->GetY();
        $pdf->MultiCell(32, 4, 'FECHA DE EMISIÓN', 'L', 'L');
        $pdf->SetXY(172, $y);
        $pdf->MultiCell(10, 4, ':', '', 'C');
        $pdf->SetXY(182, $y);
        // $time = ;
        // $newformat = ;
        $pdf->MultiCell(23, 4, date('d/m/Y',strtotime($cliente->fecha_hora)), 'R', 'L');
        $pdf->SetXY(140, 37);
        $y = $pdf->GetY();
        $pdf->MultiCell(32, 4, 'FECHA VENCIMIENTO', 'BL', 'L');
        $pdf->SetXY(172, $y);
        $pdf->MultiCell(10, 4, ':', 'B', 'C');
        $pdf->SetXY(182, $y);
        $pdf->MultiCell(23, 4, date('d/m/Y',strtotime($cliente->fecha_hora)), 'BR', 'L');
        
        # Fila descriptiva del clientes
        $pdf->Ln(1.5);
        $pdf->SetX(5);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(32, 4, 'CÓDIGO CLIENTE', 1, 'C', true, 0);
        $pdf->MultiCell(28, 4, 'NÚMERO PEDIDO', 1, 'C', true, 0);
        $pdf->MultiCell(35, 4, 'ORDEN DE COMPRA', 1, 'C', true, 0);
        $pdf->MultiCell(40, 4, 'NUMERO DE GUÍA', 1, 'C', true, 0);
        $pdf->MultiCell(40, 4, 'CONDICIONES DE PAGO', 1, 'C', true, 0);
        $pdf->MultiCell(25, 4, (!is_null(null)) ? 'DCTO. ORIGEN' : '', 1, 'C', true, 1);

        $pdf->Ln(0);
        $pdf->SetX(5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(32, 4, $cliente->num_documento, 1, 'C', false, 0);
        $pdf->MultiCell(28, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(35, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(40, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(40, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(25, 4, '', 1, 'C', false, 1);

        $pdf->Ln(1.5);
        $pdf->SetTextColor(255, 255, 255);
        $y = $pdf->GetY();
        $precVtaUnitario = 'PRECIO DE VTA. UNITARIO';
        $h = $pdf->getStringHeight(25, $precVtaUnitario);

        $pdf->MultiCell(18, $h, 'CÓDIGO', 1, 'C', true, 1, '', '', true, 0, false, true, 10, 'T');
        $pdf->SetXY(23, $y);
        $pdf->MultiCell(115, $h, 'DESCRIPCIÓN DEL ARTÍCULO', 'TRB', 'C', true, 1, '', '', true, 0, false, true, 10, 'T');
        $pdf->SetXY(138, $y);
        $pdf->MultiCell(18, $h, 'CANTIDAD', 'TRB', 'C', true, 1, '', '', true, 0, false, true, 10, 'T');
        $pdf->SetXY(156, $y);
        $pdf->MultiCell(25, 0, 'PRECIO DE VTA. UNITARIO', 'TRB', 'C', true, 1, '', '', true, 0, false, true, 10, 'Y');
        $pdf->SetXY(181, $y);
        $pdf->MultiCell(24, $h, 'TOTAL', 'TBR', 'C', true, 1, '', '', true, 0, false, true, 10, 'T');

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        $yBegin = $pdf->GetY();
        $pdf->SetTextColor(0, 0, 0);

        $array = new ArrayObject();

        $total = 0;
        foreach ($items as $i) {
            $item = new stdClass();
            $item->codigo = $i->codigo;
            $item->descripcion = $i->nombre;
            $item->cantidad = $i->cantidad;
            $item->precio = $i->precio_venta;
            $item->total = $i->total;
            $array->append($item);
            $total = $total + $i->total;
        }

        $neto = $total / 1.18;
        $igv = $total - $neto;


        // $item = new stdClass();
        // $item->codigo = "102";
        // $item->descripcion = "LICENCIA DE USO DEL MÓDULO DE VENTAS";
        // $item->cantidad = "100.00";
        // $item->precio = '100.00';
        // $item->total = '100.00';
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);
        // $array->append($item);

        // $array2 = $array;

        foreach ($array as $key => $value) {
            $y = $pdf->GetY();
            $x = 5;
            $description = '';
            $h = 0;

            // foreach ($array2 as $k => $v) {
            //     if ($k != 0) {
            //         $description .= "\n";
            //     }
            //     $description .= $v->descripcion;
                $h += $pdf->getStringHeight(115, $value->descripcion);
            // }

            # Crear nueva página cuando te acercas a su límite
            if (($y + $h) > 250) {
                $h = 250 - $y;
                $pdf->MultiCell(18, $h, '', 'LRB', 'L', false, 1, '', '', true, 0, false, false, $h);
                $pdf->SetXY($x + 18, $y);
                $x = $pdf->GetX();
                $pdf->MultiCell(115, $h, '', 'RB', 'L', false, 1, '', '', true, 0, false, false, $h);
                $pdf->SetXY($x + 115, $y);
                $x = $pdf->GetX();
                $pdf->MultiCell(18, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
                $pdf->SetXY($x + 18, $y);
                $x = $pdf->GetX();
                $pdf->MultiCell(25, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
                $pdf->SetXY($x + 25, $y);
                $x = $pdf->GetX();
                $pdf->MultiCell(24, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
                $y = $yBegin;
                $x = 5;
                $pdf->AddPage();
            } else if (($y + $h) == 250) {
                $y = $yBegin;
                $pdf->AddPage();
            }
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(18, $h, $value->codigo, 'LR', 'L');
            // LOG::info("---------------ID---------------");
            // LOG::info($value->c_item_sellers_item_identification_id);
            // LOG::info("---------------ID---------------");
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(115, $h, $value->descripcion, 'R', 'L');
            // LOG::info("---------------DESCRIPCION---------------");
            // LOG::info($description);
            // LOG::info("------------------------------");
            $pdf->SetXY($x + 115, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(18, $h, $value->cantidad, 'R', 'R');
            // LOG::info("---------------C-INVOICED-QUANTITY---------------");
            // LOG::info($value->c_invoiced_quantity);
            // LOG::info("------------------------------");
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(25, $h, $value->precio, 'R', 'R');
            // LOG::info("---------------PRICE-AMOUNT---------------");
            // LOG::info($value->c_price_price_amount);
            // LOG::info("------------------------------");
            $pdf->SetXY($x + 25, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(24, $h, $value->total, 'R', 'R');
            // LOG::info("---------------LINE-EXTENSION-AMOUNT---------------");
            // LOG::info($value->c_line_extension_amount);
            // LOG::info("------------------------------");
        }

        # Textos adicionales #
        $append = [];
        // LOG::info("---------------EMISOR DETRACCION-------------------");
        // LOG::info($data['emisor_detraccion']);
        // LOG::info("---------------LEYENDA-------------------");
        // LOG::info($data['leyenda']);
        // LOG::info("---------------MONTO EN LETRAS-------------------");
        // LOG::info($data['monto_en_letras']);
        
            // $append[] = '00098066799';
        
            $append[] = '';
        
            $append[] = '';
        
            $append[] = 'SON: '.$leyenda .' SOLES';
        
            // $append[] = "S.E.U.O.";
        
        $height = 0;
        foreach ($append as $value) {
            $height += $pdf->getStringHeight(115, $value);
        }
        $pdf->SetX(5);
        $y = $pdf->GetY();
        $diff = 250 - $height - $y;
        $x = 5;
        if ((250 - $y) < $height) {
            $h = 250 - $y;
            $pdf->MultiCell(18, $h, '', 'LRB', 'L', false, 1, '', '', true, 0, false, false, $h);
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(115, $h, '', 'RB', 'L', false, 1, '', '', true, 0, false, false, $h);
            $pdf->SetXY($x + 115, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(18, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(25, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
            $pdf->SetXY($x + 25, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(24, $h, '', 'RB', 'R', false, 1, '', '', true, 0, false, false, $h);
            $y = $yBegin;
            $x = 5;
            $pdf->AddPage();
            $diff = 250 - $height - $y;
        }
        $pdf->MultiCell(18, $diff, '', 'LR', 'L', false, 1, '', '', true, 0, false, false, $diff);
        $pdf->SetXY($x + 18, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(115, $diff, '', 'R', 'L', false, 1, '', '', true, 0, false, false, $diff);
        $pdf->SetXY($x + 115, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(18, $diff, '', 'R', 'R', false, 1, '', '', true, 0, false, false, $diff);
        $pdf->SetXY($x + 18, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(25, $diff, '', 'R', 'R', false, 1, '', '', true, 0, false, false, $diff);
        $pdf->SetXY($x + 25, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(24, $diff, '', 'R', 'R', false, 1, '', '', true, 0, false, false, $diff);

        foreach ($append as $key => $value) {
            $b = '';
            if ((count($append) - 1) == $key) {
                $b = 'B';
            }
            $y = $pdf->GetY();
            $x = $pdf->GetX();
            $h = $pdf->getStringHeight(115, $value);

            // LOG::info("--------------------VALUE--------------------");
            LOG::info($value);
            $pdf->MultiCell(18, $h, '', 'LR' . $b, 'L');
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(115, $h, $value, 'R' . $b, 'L');
            $pdf->SetXY($x + 115, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(18, $h, '', 'R' . $b, 'L');
            $pdf->SetXY($x + 18, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(25, $h, '', 'R' . $b, 'L');
            $pdf->SetXY($x + 25, $y);
            $x = $pdf->GetX();
            $pdf->MultiCell(24, $h, '', 'R' . $b, 'L');
        }

        # Pie de página
        $pdf->Ln(1.5);
        $pdf->SetTextColor(255, 255, 255);
        $y = $pdf->GetY();
        $x = $pdf->GetX();
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(30, 4, 'OP. EXONERADA', 1, 'C', true);
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, 'OP. INAFECTA', 1, 'C', true);
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, 'OP. GRAVADA', 1, 'C', true);
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, 'TOT. DSCTO.', 1, 'C', true);
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, 'I.S.C.', 1, 'C', true);
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, 'I.G.V.', 1, 'C', true);
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(40, 4, 'IMPORTE TOTAL', 1, 'C', true);

        $pdf->Ln(0);
        $pdf->SetTextColor(0, 0, 0);
        $y = $pdf->GetY();
        $x = $pdf->GetX();
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(30, 4, '0.00', 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, '0.00', 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, number_format(round($neto,2),2,'.',''), 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, '', 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, '', 1, 'R');
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, number_format(round($igv,2),2,'.',''), 1, 'R');
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(40, 4, number_format(round($total,2),2,'.',''), 1, 'R');

        $pdf->Ln(1.5);
        $y = $pdf->GetY();
        $x = $pdf->GetX();
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->MultiCell(100, 0, 'Representación Impresa de la ' . "FACTURA ELECTRÓNICA", '', 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(100, 0, 'Autorizado mediante Resolución de Intendencia Nº ' . "034-005-0006241/SUNAT", '', 'C');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->MultiCell(100, 0, 'Consulta tu comprobante en nuestra web ' . env('URL_CONSULTA_WEB'), '', 'C');
        //$pdf->write2DBarcode($data['codigoBarra'], 'PDF417', $x + 140, $y, 70);
        $qr = $empresa->ruc."|".$cliente->tipo_comprobante."|".$cliente->serie_comprobante."|".$cliente->num_comprobante."|".round($igv,2)."|".round($total,2)."|".date('Y-m-d',strtotime($cliente->fecha_hora))."|"."|6|".$cliente->num_documento."|".$firma;
        $pdf->write2DBarcode($qr, 'QRCODE,L', $x + 180, $y, 20);








        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.

        $nombreFactura = $empresa->ruc."-".$cliente->tipo_comprobante."-".$cliente->serie_comprobante."-".$cliente->num_comprobante.".pdf";


        $pdf->Output(public_path().'\cdn/pdf/'.$nombreFactura, 'F');

        //============================================================+
        // END OF FIL
        //============================================================+


    }

    public function crearPDFA7($empresa,$cliente,$items,$leyenda,$firma){
        $h = 0;

        $pdf = new TCPDF('P', 'mm');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(1, 1);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('helvetica', '', 8);

        ##
        $data = array(
            'ticket_titulo_1' => $empresa->nombre_comercial,
            'ticket_titulo_2' => 'RUC N° '.$empresa->ruc,
            'c_party_postal_address_street_name' => $empresa->direccion,
            'c_party_postal_address_district' => $empresa->departamento.' '.$empresa->provincia.' '.$empresa->distrito,
            'emisor_ruc' => $empresa->ruc,
            'c_party_postal_address_city_subdivision_name' => '',
            'nombre_de_comprobante' => 'BOLETA DE VENTA ELECTRÓNICA',
            'emisor_ruc' => $empresa->ruc,
            'c_telephone' => $empresa->telefono,
            'serie_ticket' => $cliente->serie_comprobante,
            's_caja' => 'S/. 100',
            'cliente_razon_social' => $cliente->nombre,
            'cliente_documento' => $cliente->num_documento,
            'c_customer_assigned_account_id' => '06',
            'cliente_direccion' => $cliente->direccion,
            'paciente' => 'Irving Ortega Zarabia',
            'prf_nro' => '100',
            'hc' => '50',
            'items' => $items,
            'pago_con' => 'EFECTIVO',
            'vuelto' => '0.00',
            'usuario' => 'IMOZ',
            'fecha_emision_hora' => date('H:i:s',strtotime($cliente->fecha_hora)),
            'fecha_emision' => date('Y/m/d',strtotime($cliente->fecha_hora)),
            'serie' => $cliente->serie_comprobante,
            'correlativo' => $cliente->num_comprobante,
            'path' => public_path().'\cdn/pdf/'.'B001-00000001.pdf',
            'total' => '200.00',
        );

        // $pdf->SetFont('helvetica','',12);
        $h += $pdf->getStringHeight(72, '');

        # Calculo de alto de pagina
        if (isset($data['ticket_titulo_1']) && !empty($data['ticket_titulo_1'])) {
            $h += $pdf->getStringHeight(72, $data['ticket_titulo_1']);
        }
        if (isset($data['ticket_titulo_2']) && !empty($data['ticket_titulo_2'])) {
            $h += $pdf->getStringHeight(72, $data['ticket_titulo_2']);
        }

        // $pdf->SetFont('helvetica','',8);

        $h += $pdf->getStringHeight(72,
            $data['c_party_postal_address_street_name'] . ' ' . $data['c_party_postal_address_city_subdivision_name']);

        $h += $pdf->getStringHeight(72, $data['c_party_postal_address_district']);
        $h += $pdf->getStringHeight(72, ' TLF: ' . $data['c_telephone']);

        // $pdf->SetFont('helvetica','',10);        
        if (isset($data['nombre_de_comprobante']) && !empty($data['nombre_de_comprobante'])) {
            $h += $pdf->getStringHeight(72, $data['nombre_de_comprobante']);
        }
        $h += $pdf->getStringHeight(72, 'RUC: ' . $data['emisor_ruc'] . ' TLF: ' . $data['c_telephone']);
        $h += $pdf->getStringHeight(72, $data['serie'].'-'.$data['correlativo']);
        $h += $pdf->getStringHeight(72, '');

        // if (isset($data['serie_ticket']) && !empty($data['serie_ticket'])) {
        //     $h += $pdf->getStringHeight(72, $data['serie_ticket']);
        // }

        $h += $pdf->getStringHeight(72, 'FECHA DE EMISIÓN');
        $h += $pdf->getStringHeight(72, 'CAJERO');
        $h += $pdf->getStringHeight(72, '=========================================');
        $h += $pdf->getStringHeight(38, $data['cliente_razon_social']);
        $h += $pdf->getStringHeight(38, $data['cliente_documento']);
        $h += $pdf->getStringHeight(38, $data['cliente_direccion']);
        $h += $pdf->getStringHeight(72, '=========================================');

        // $h += $pdf->getStringHeight(70, 'FECHA');
        // $h += $pdf->getStringHeight(70, 'TICKET');

        // if (isset($data['s_caja']) && !empty($data['s_caja'])) {
        //     $h += $pdf->getStringHeight(70, 's_caja');
        // }
        // if (isset($data['cliente_razon_social']) && !empty($data['cliente_razon_social'])) {
        //     $h += $pdf->getStringHeight(52, $data['cliente_razon_social']);
        // }

        // $h += $pdf->getStringHeight(52, $data['c_customer_assigned_account_id']);

        // if (isset($data['cliente_direccion']) && !empty($data['cliente_direccion'])) {
        //     $h += $pdf->getStringHeight(52, $data['cliente_direccion']);
        // }
        // if (isset($data['paciente']) && !empty($data['paciente'])) {
        //     $h += $pdf->getStringHeight(52, $data['paciente']);
        // }
        // if (isset($data['prf_nro']) && !empty($data['prf_nro'])) {
        //     $h += $pdf->getStringHeight(52, $data['prf_nro']);
        // }
        // if (isset($data['hc']) && !empty($data['hc'])) {
        //     $h += $pdf->getStringHeight(52, $data['hc']);
        // }
        // $h += $pdf->getStringHeight(70, '=========================================');
        $h += $pdf->getStringHeight(70, 'CANT');
        $h += $pdf->getStringHeight(70, '=========================================');
        foreach ($data['items'] as $value) {
            // foreach ($value->DocInvoiceItemDescription as $k => $v) {
                $h += $pdf->getStringHeight(42, $value->nombre);
            // }
        }
        $h += $pdf->getStringHeight(30, 'OP.GRAVADA');
        $h += $pdf->getStringHeight(30, 'OP. INAFECTA');
        $h += $pdf->getStringHeight(30, 'OP. EXONERADA');
        $h += $pdf->getStringHeight(30, 'IGV');
        $h += $pdf->getStringHeight(72, '=========================================');
        $h += $pdf->getStringHeight(30, 'TOTAL');
        $h += $pdf->getStringHeight(72, '=========================================');
        $h += $pdf->getStringHeight(62, $leyenda);
        $h += $pdf->getStringHeight(72, '');
        $h += $pdf->getStringHeight(72, '');
        $h += $pdf->getStringHeight(72, '');
        $h += $pdf->getStringHeight(72, '');
        $h += $pdf->getStringHeight(72, '');
        $h += $pdf->getStringHeight(72, '');
        // $pdf->SetFont('helveticaI', '', 5);
        $h += $pdf->getStringHeight(72, $firma);
        $h += $pdf->getStringHeight(72, 'Este documento puede ser validado en la siguiente dirección: https://perusi.pe');
        // if (isset($data['pago_con']) && !empty($data['pago_con'])) {
        //     $h += $pdf->getStringHeight(42, $data['pago_con']);
        // }
        // if (isset($data['vuelto']) && !empty($data['vuelto'])) {
        //     $h += $pdf->getStringHeight(42, $data['vuelto']);
        // }
        // if (isset($data['usuario']) && !empty($data['usuario'])) {
        //     $h += $pdf->getStringHeight(42, $data['usuario']);
        // }


        //PINTADO DE PAGINA

        ##
        $pdf->SetFont('helvetica', '', 12);

        $pdf->AddPage('P', [74, $h + 5]);

        $pdf->MultiCell(72, 0, '', '', 'C');

        if (isset($data['ticket_titulo_1']) && !empty($data['ticket_titulo_1'])) {
            $pdf->MultiCell(72, 0, $data['ticket_titulo_1'], '', 'C');
        }
        if (isset($data['ticket_titulo_2']) && !empty($data['ticket_titulo_2'])) {
            $pdf->MultiCell(72, 0, $data['ticket_titulo_2'], '', 'C');
        }
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(72, 0,
            $data['c_party_postal_address_street_name'] . ' ' . $data['c_party_postal_address_city_subdivision_name'],
            '', 'C');
        $pdf->MultiCell(72, 0, $data['c_party_postal_address_district'], '', 'C');
        $pdf->MultiCell(72, 0, ' TLF: ' . $data['c_telephone'], '', 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(72, 0, $data['nombre_de_comprobante'], '', 'C');
        $pdf->MultiCell(72, 0, $data['serie'].'-'.$data['correlativo'], '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(30, 0, 'FECHA DE EMISIÓN', '', 'L', false, 0);
        $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        if (isset($data['fecha_emision_hora']) && !empty($data['fecha_emision_hora'])) {
            $pdf->MultiCell(38, 0, $data['fecha_emision'].' - '.$data['fecha_emision_hora'], '', 'L', false, 1);
            // $pdf->MultiCell(12, 0, 'HORA', '', 'R', false, 0);
            // $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
            // $pdf->MultiCell(16, 0, $data['fecha_emision_hora'], '', 'L', false, 1);
        } else {
            // $pdf->MultiCell(20, 0, $data['fecha_emision'], '', 'L', false, 1);
        }
        $pdf->MultiCell(30, 0, 'CAJERO', '', 'L', false, 0);
        $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        $pdf->MultiCell(38, 0, $data['usuario'], '', 'L', false, 1);
        $pdf->MultiCell(70, 0, '=========================================', '', 'C');
        // if (isset($data['s_caja']) && !empty($data['s_caja'])) {
        //     $pdf->MultiCell(14, 0, 'S-CAJA', '', 'L', false, 0);
        //     $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        //     $pdf->MultiCell(52, 0, $data['s_caja'], '', 'L', false, 1);
        // }
        if (isset($data['cliente_razon_social']) && !empty($data['cliente_razon_social'])) {
            $pdf->MultiCell(30, 0, 'Cliente', '', 'L', false, 0);
            $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
            $pdf->MultiCell(38, 0, $data['cliente_razon_social'], '', 'L', false, 1);
        }
        $pdf->MultiCell(30, 0, 'RUC', '', 'L', false, 0);
        $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        $pdf->MultiCell(38, 0, $data['cliente_documento'], '', 'L', false, 1);
        if (isset($data['cliente_direccion']) && !empty($data['cliente_direccion'])) {
            $pdf->MultiCell(30, 0, 'Dirección', '', 'L', false, 0);
            $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
            $pdf->MultiCell(38, 0, $data['cliente_direccion'], '', 'L', false, 1);
        }
        // if (isset($data['paciente']) && !empty($data['paciente'])) {
        //     $pdf->MultiCell(30, 0, 'Paciente', '', 'L', false, 0);
        //     $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        //     $pdf->MultiCell(38, 0, $data['paciente'], '', 'L', false, 1);
        // }
        // if (isset($data['prf_nro']) && !empty($data['prf_nro'])) {
        //     $pdf->MultiCell(30, 0, 'Prf. N.', '', 'L', false, 0);
        //     $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        //     $pdf->MultiCell(38, 0, $data['prf_nro'], '', 'L', false, 1);
        // }
        // if (isset($data['hc']) && !empty($data['hc'])) {
        //     $pdf->MultiCell(30, 0, 'H.C.', '', 'L', false, 0);
        //     $pdf->MultiCell(4, 0, ':', '', 'C', false, 0);
        //     $pdf->MultiCell(38, 0, $data['hc'], '', 'L', false, 1);
        // }
        $pdf->MultiCell(70, 0, '=========================================', '', 'C');
        $pdf->MultiCell(11, 0, 'CANT.', '', 'L', false, 0);
        $pdf->MultiCell(42, 0, 'DESCRIPCIÓN', '', 'L', false, 0);
        $pdf->MultiCell(17, 0, 'MONTO S/.', '', 'L', false, 1);
        $pdf->MultiCell(70, 0, '=========================================', '', 'C');
        $total = 0;
        foreach ($data['items'] as $value) {
            $description = '';
            $h = 0;
            // foreach ($value->DocInvoiceItemDescription as $k => $v) {
            //     if ($k != 0) {
            //         $description .= "\n";
            //     }
            //     $description .= $v->c_description;
            //     $h += $pdf->getStringHeight(42, $v->c_description);
            // }
            $description .= $value->nombre;
            $h += $pdf->getStringHeight(42, $value->nombre);

            $pdf->MultiCell(11, $h, $value->cantidad, '', 'L', false, 0, '', '', true, 0, false, false, $h);

            $pdf->MultiCell(42, $h, $description, '', 'L', false, 0, '', '', true, 0, false, false, $h);
            $pdf->MultiCell(17, $h, $value->precio_venta, '', 'R', false, 1, '', '', true, 0, false, false,
                $h);

            $total = $total + ($value->cantidad*$value->precio_venta);
            
        }
        $neto = $total / 1.18;
        $igv = $total - $neto;  
        $pdf->MultiCell(30, 0, 'OP. GRAVADA', '', 'L', false, 0);
        $pdf->MultiCell(8, 0, 'S/.', '', 'C', false, 0);
        $pdf->MultiCell(34, 0, round($neto,2), '', 'R', false, 1);
        $pdf->MultiCell(30, 0, 'OP. INAFECTA', '', 'L', false, 0);
        $pdf->MultiCell(8, 0, 'S/.', '', 'C', false, 0);
        $pdf->MultiCell(34, 0, '0.00', '', 'R', false, 1);
        $pdf->MultiCell(30, 0, 'OP. EXONERADA', '', 'L', false, 0);
        $pdf->MultiCell(8, 0, 'S/.', '', 'C', false, 0);
        $pdf->MultiCell(34, 0, '0.00', '', 'R', false, 1);
        $pdf->MultiCell(30, 0, 'I.G.V.', '', 'L', false, 0);
        $pdf->MultiCell(8, 0, 'S/.', '', 'C', false, 0);
        $pdf->MultiCell(34, 0, round($igv,2), '', 'R', false, 1);
        $pdf->MultiCell(70, 0, '=========================================', '', 'C');
        $pdf->MultiCell(30, 0, 'TOTAL', '', 'L', false, 0);
        $pdf->MultiCell(8, 0, 'S/.', '', 'R', false, 0);
        $pdf->MultiCell(34, 0, number_format(round($total,2),2,'.',''), '', 'R', false, 1);
        $pdf->MultiCell(70, 0, '=========================================', '', 'C');
        $pdf->MultiCell(10, 0, 'SON : ', '', 'L', false, 0);
        $pdf->MultiCell(62, 0, $leyenda, '', 'L', false, 1);
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->write2DBarcode("123456", 'QRCODE,L', $pdf->GetX() + 25, $pdf->GetY(), 20);
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->MultiCell(72, 0, '', '', 'C');
        $pdf->SetFont('helveticaI', '', 5);
        $pdf->MultiCell(72, 0, $firma, '', 'C');
        $pdf->MultiCell(72, 0, 'Este documento puede ser validado en la siguiente dirección: https://perusi.pe', '', 'C');
        
        // if (isset($data['pago_con']) && !empty($data['pago_con'])) {
        //     $pdf->MultiCell(20, 0, 'PAGO CON', '', 'L', false, 0);
        //     $pdf->MultiCell(8, 0, 'S/.:', '', 'R', false, 0);
        //     $pdf->MultiCell(42, 0, $data['pago_con'], '', 'R', false, 1);
        // }
        // if (isset($data['vuelto']) && !empty($data['vuelto'])) {
        //     $pdf->MultiCell(20, 0, 'VUELTO', '', 'L', false, 0);
        //     $pdf->MultiCell(8, 0, 'S/.:', '', 'R', false, 0);
        //     $pdf->MultiCell(42, 0, $data['vuelto'], '', 'R', false, 1);
        // }
        // if (isset($data['usuario']) && !empty($data['usuario'])) {
        //     $pdf->MultiCell(20, 0, 'Usuario', '', 'L', false, 0);
        //     $pdf->MultiCell(8, 0, ':', '', 'R', false, 0);
        //     $pdf->MultiCell(42, 0, $data['usuario'], '', 'R', false, 1);
        // }

        $nombreFactura = $empresa->ruc."-".$cliente->tipo_comprobante."-".$cliente->serie_comprobante."-".$cliente->num_comprobante.".pdf";

        $pdf->Output(public_path().'\cdn/pdf/'.$nombreFactura, 'F');
        chmod(public_path().'\cdn/pdf/'.$nombreFactura, 0777);


    }


    public function readCdr($nIdInvoice, $path, $invoiceTypeCode)
    {

        $response['status'] = 0;
        $response['code'] = '';
        $response['message'] = '';
        $message = '';

        // $path = 'C:\xampp1\htdocs\sisVentas\public\cdn/cdr\R-20536579746-01-F001-00004483.ZIP';


        if(env('SYSTEM')=='linux')
            $nameExplode = explode('/', $path);
        elseif(env('SYSTEM')=='windows'){
            $nameExplode = explode('\\', $path);
            //TODO fix var $name
        }

        $name = end($nameExplode);
        $nameCdrExplode = explode('.', $name);
        $nameCdr = reset($nameCdrExplode) . '.XML';
        if(file_exists($path)){
            LOG::info("SI EXISTE EL ARCHIVO");
        }else{
            LOG::info("NO EXISTE EL ARCHIVO");
        }

        \Zipper::make($path)->extractTo(storage_path('sunat/tmp'));
        $pathCdr = storage_path('sunat/tmp/' . $nameCdr);

        try {
            $cdr = $this->cdr($pathCdr);

            # Grabar respuesta (CDR) en la base de Datos.
            // $docInvoiceCdr = DocInvoiceCdr::destroy($nIdInvoice);

            // $docInvoiceCdr = new DocInvoiceCdr();
            // $docInvoiceCdr->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdr->c_ubl_version_id = (isset($cdr['UBLVersionID'])) ? $cdr['UBLVersionID'] : null;
            // $docInvoiceCdr->c_customization_id = (isset($cdr['CustomizationID'])) ? $cdr['CustomizationID'] : null;
            // $docInvoiceCdr->c_id = $cdr['ID'];
            // $docInvoiceCdr->d_issue_date = $cdr['IssueDate'];
            // $docInvoiceCdr->d_issue_time = $cdr['IssueTime'];
            // $docInvoiceCdr->d_response_date = $cdr['ResponseDate'];
            // $docInvoiceCdr->d_response_time = $cdr['ResponseTime'];
            // $docInvoiceCdr->save();

            // $docInvoiceCdrSenderParty = new DocInvoiceCdrSenderParty();
            // $docInvoiceCdrSenderParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrSenderParty->c_party_identification_id = $cdr['SenderParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrSenderParty->save();

            // $docInvoiceCdrReceiverParty = new DocInvoiceCdrReceiverParty();
            // $docInvoiceCdrReceiverParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrReceiverParty->c_party_identification_id = $cdr['ReceiverParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrReceiverParty->save();

            // $docInvoiceCdrDocumentResponse = new DocInvoiceCdrDocumentResponse();
            // $docInvoiceCdrDocumentResponse->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrDocumentResponse->c_response_reference_id = $cdr['DocumentResponse']['Response']['ReferenceID'];
            // $docInvoiceCdrDocumentResponse->c_response_response_code = $cdr['DocumentResponse']['Response']['ResponseCode'];
            LOG::info("-------------------RESPUESTA XML-------------------");
            LOG::info($cdr['DocumentResponse']['Response']['ResponseCode']);
            // $docInvoiceCdrDocumentResponse->c_response_description = $cdr['DocumentResponse']['Response']['Description'];
            // $docInvoiceCdrDocumentResponse->c_document_reference_id = $cdr['DocumentResponse']['DocumentReference']['ID'];
            // $docInvoiceCdrDocumentResponse->c_recipient_party_party_identification_id = $cdr['DocumentResponse']['RecipientParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrDocumentResponse->save();

            if (isset($cdr['Note'])) {
                foreach ($cdr['Note'] as $key => $value) {
                    $note = (string) $value;
                    $noteExplode = explode(' - ', $note);
                    // $docInvoiceCdrNote = new DocInvoiceCdrNote();
                    // $docInvoiceCdrNote->n_id_invoice = $nIdInvoice;
                    // $docInvoiceCdrNote->c_note = $note;
                    // $docInvoiceCdrNote->c_code = reset($noteExplode);
                    // $docInvoiceCdrNote->c_description = end($noteExplode);
                    // $docInvoiceCdrNote->save();
                }
            }

            // $docInvoiceCdrStatus = DocInvoiceCdrStatus::find($nIdInvoice);
            switch ($cdr['DocumentResponse']['Response']['ResponseCode']) {
                case '0':
                    $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
                    if (isset($cdr['Note'])) {
                        # Observado
                        foreach ($cdr['Note'] as $key => $value) {
                            $response['message'][$key] = $value;
                        }
                        $message = implode(', ', $response['message']);
                        // $docInvoiceCdrStatus->n_id_cdr_status = 3;
                    } else {
                        # Aceptado
                        // $docInvoiceCdrStatus->n_id_cdr_status = 1;
                        $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
                        LOG::info($cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue']);
                        // $docInvoiceFile = DocInvoiceFile::find($nIdInvoice);
                        // $docInvoiceFile->c_has_sunat_successfully_passed = 'yes';
                        // $docInvoiceFile->save();
                        $message = $response['message'];
                    }
                    break;
                default:
                    # Rechazado
                    $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
                    $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
                    $errErrorCode = ErrErrorCode::find($response['code']);
                    $message = $errErrorCode->c_description;
                    // $docInvoiceCdrStatus->n_id_cdr_status = 2;
                    break;
            }
            // $docInvoiceCdrStatus->save();

            $response['status'] = 1;

            Log::info($message,
                [
                'lgph_id' => 6, 'n_id_invoice' => $nIdInvoice,
                'c_id_error_code' => $cdr['DocumentResponse']['Response']['ResponseCode'],
                'c_invoice_type_code' => $invoiceTypeCode,
                ]
            );
        } catch (Exception $exc) {
            $response['message'] = $exc->getMessage();

            Log::error($response['message'],
                [
                'lgph_id' => 6, 'n_id_invoice' => $nIdInvoice, 'c_invoice_type_code' => $invoiceTypeCode,
                ]
            );
        }

        unlink($pathCdr);

        return $response;
    }

    public function cdr($path)
    {
        if (!file_exists($path)) {
            throw new Exception('No existe el archivo XML del CDR.');
        }

        $cdr = [];

        $xml = simplexml_load_file($path, null, LIBXML_NOCDATA);
        LOG::info("--------------------LEENDO EL CDR--------------------");
        $namespaces = $xml->getNamespaces(true);
        $dataCac = $xml->children($namespaces['cac']);
        $dataCbc = $xml->children($namespaces['cbc']);

        foreach ($dataCbc as $key => $value) {
            LOG::info($key."-".$value);
            switch ($key) {
                case 'UBLVersionID':
                case 'CustomizationID':
                case 'ID':
                case 'IssueDate':
                case 'IssueTime':
                case 'ResponseDate':
                case 'ResponseTime':
                    $cdr[$key] = (string) $value;
                    break;
                case 'Note':
                    $cdr[$key][] = (string) $value;
                    break;
            }
        }

        foreach ($dataCac as $key => $value) {
            switch ($key) {
                case 'SenderParty':
                case 'ReceiverParty':
                case 'DocumentResponse':
                    foreach ($value->children($namespaces['cac']) as $ke => $va) {
                        switch ($ke) {
                            case 'PartyIdentification':
                            case 'Response':
                            case 'DocumentReference':
                                foreach ($va->children($namespaces['cbc']) as $k => $v) {
                                    switch ($k) {
                                        case 'ID':
                                        case 'ReferenceID':
                                        case 'ResponseCode':
                                        case 'Description':
                                        
                                            $cdr[$key][$ke][$k] = (string) $v;
                                            break;
                                    }
                                }
                                break;
                            case 'RecipientParty':
                                foreach ($va->children($namespaces['cac']) as $k => $v) {
                                    switch ($k) {
                                        case 'PartyIdentification':
                                            foreach ($v->children($namespaces['cbc']) as $l => $b) {
                                                $cdr[$key][$ke][$k][$l] = (string) $b;
                                            }
                                            break;
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        return $cdr;
    }

    public function readSignDocument($path)
    {

        $response['status'] = 0;
        $response['code'] = '';
        $response['message'] = '';
        $message = '';

        // $path = 'C:\xampp1\htdocs\sisVentas\public\cdn/cdr\R-20536579746-01-F001-00004483.ZIP';
        LOG::info("readSignDocument--".$path);

        if(env('SYSTEM')=='linux')
            $nameExplode = explode('/', $path);
        elseif(env('SYSTEM')=='windows'){
            $nameExplode = explode('\\', $path);
            //TODO fix var $name
        }



        $name = end($nameExplode);
        $nameCdrExplode = explode('.', $name);
        $nameCdr = reset($nameCdrExplode) . '.XML';
        if(file_exists($path)){
            LOG::info("SI EXISTE EL ARCHIVO ZIP");
        }else{
            LOG::info("NO EXISTE EL ARCHIVO ZIP");
        }
        LOG::info("readSignDocument--".$nameCdr);

        \Zipper::make($path)->extractTo(storage_path('sunat/tmp'));
        $pathCdr = storage_path('sunat/tmp/' . $nameCdr);

        try {
            $cdr = $this->documentXML($pathCdr);

            # Grabar respuesta (CDR) en la base de Datos.
            // $docInvoiceCdr = DocInvoiceCdr::destroy($nIdInvoice);

            // $docInvoiceCdr = new DocInvoiceCdr();
            // $docInvoiceCdr->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdr->c_ubl_version_id = (isset($cdr['UBLVersionID'])) ? $cdr['UBLVersionID'] : null;
            // $docInvoiceCdr->c_customization_id = (isset($cdr['CustomizationID'])) ? $cdr['CustomizationID'] : null;
            // $docInvoiceCdr->c_id = $cdr['ID'];
            // $docInvoiceCdr->d_issue_date = $cdr['IssueDate'];
            // $docInvoiceCdr->d_issue_time = $cdr['IssueTime'];
            // $docInvoiceCdr->d_response_date = $cdr['ResponseDate'];
            // $docInvoiceCdr->d_response_time = $cdr['ResponseTime'];
            // $docInvoiceCdr->save();

            // $docInvoiceCdrSenderParty = new DocInvoiceCdrSenderParty();
            // $docInvoiceCdrSenderParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrSenderParty->c_party_identification_id = $cdr['SenderParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrSenderParty->save();

            // $docInvoiceCdrReceiverParty = new DocInvoiceCdrReceiverParty();
            // $docInvoiceCdrReceiverParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrReceiverParty->c_party_identification_id = $cdr['ReceiverParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrReceiverParty->save();

            // $docInvoiceCdrDocumentResponse = new DocInvoiceCdrDocumentResponse();
            // $docInvoiceCdrDocumentResponse->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrDocumentResponse->c_response_reference_id = $cdr['DocumentResponse']['Response']['ReferenceID'];
            // $docInvoiceCdrDocumentResponse->c_response_response_code = $cdr['DocumentResponse']['Response']['ResponseCode'];
    LOG::info("-------------------RESPUESTA XML-------------------");
    LOG::info($cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue']);
            // $docInvoiceCdrDocumentResponse->c_response_description = $cdr['DocumentResponse']['Response']['Description'];
            // $docInvoiceCdrDocumentResponse->c_document_reference_id = $cdr['DocumentResponse']['DocumentReference']['ID'];
            // $docInvoiceCdrDocumentResponse->c_recipient_party_party_identification_id = $cdr['DocumentResponse']['RecipientParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrDocumentResponse->save();

            if (isset($cdr['Note'])) {
                foreach ($cdr['Note'] as $key => $value) {
                    $note = (string) $value;
                    $noteExplode = explode(' - ', $note);
                    // $docInvoiceCdrNote = new DocInvoiceCdrNote();
                    // $docInvoiceCdrNote->n_id_invoice = $nIdInvoice;
                    // $docInvoiceCdrNote->c_note = $note;
                    // $docInvoiceCdrNote->c_code = reset($noteExplode);
                    // $docInvoiceCdrNote->c_description = end($noteExplode);
                    // $docInvoiceCdrNote->save();
                }
            }

            // $docInvoiceCdrStatus = DocInvoiceCdrStatus::find($nIdInvoice);
            // switch ($cdr['DocumentResponse']['Response']['ResponseCode']) {
            //     case '0':
            //         $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
            //         if (isset($cdr['Note'])) {
            //             # Observado
            //             foreach ($cdr['Note'] as $key => $value) {
            //                 $response['message'][$key] = $value;
            //             }
            //             $message = implode(', ', $response['message']);
            //             // $docInvoiceCdrStatus->n_id_cdr_status = 3;
            //         } else {
            //             # Aceptado
            //             // $docInvoiceCdrStatus->n_id_cdr_status = 1;
            //             $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
            //             // LOG::info($cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue']);
            //             // $docInvoiceFile = DocInvoiceFile::find($nIdInvoice);
            //             // $docInvoiceFile->c_has_sunat_successfully_passed = 'yes';
            //             // $docInvoiceFile->save();
            //             $message = $response['message'];
            //         }
            //         break;
            //     default:
            //         # Rechazado
            //         $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
            //         $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
            //         $errErrorCode = ErrErrorCode::find($response['code']);
            //         $message = $errErrorCode->c_description;
            //         // $docInvoiceCdrStatus->n_id_cdr_status = 2;
            //         break;
            // }
            // $docInvoiceCdrStatus->save();
            $response['sign'] = $cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue'];
            $response['status'] = 1;

            // Log::info($message,
            //     [
            //     'lgph_id' => 6, 'n_id_invoice' => $nIdInvoice,
            //     'c_id_error_code' => $cdr['DocumentResponse']['Response']['ResponseCode'],
            //     'c_invoice_type_code' => $invoiceTypeCode,
            //     ]
            // );
        } catch (Exception $exc) {
            $response['message'] = $exc->getMessage();

            Log::error($response['message'],
                [
                'lgph_id' => 6, 'n_id_invoice' => '$nIdInvoice', 'c_invoice_type_code' => '$invoiceTypeCode',
                ]
            );
        }

        unlink($pathCdr);

        return $response;
    }

    public function documentXML($path)
    {
        if (!file_exists($path)) {
            throw new Exception('No existe el archivo XML del CDR.');
        }

        $cdr = [];

        $xml = simplexml_load_file($path, null, LIBXML_NOCDATA);
        LOG::info("--------------------LEENDO EL DOCUMENTO--------------------");
        $namespaces = $xml->getNamespaces(true);
        $dataExt = $xml->children($namespaces['ext']);
        $dataCac = $xml->children($namespaces['cac']);
        $dataCbc = $xml->children($namespaces['cbc']);

        // READING HASH FROM INVOICE
        foreach ($dataExt as $key => $value) {
            switch ($key) {
                case 'UBLExtensions':
                    foreach ($value->children($namespaces['ext']) as $ke => $va) {
                        switch ($ke) {
                            case 'UBLExtension':
                                foreach ($va->children($namespaces['ext']) as $k => $v) {
                                    switch ($k) {
                                        case 'ExtensionContent':
                                            foreach ($v->children($namespaces['ds']) as $key1 => $value1) {
                                                switch ($key1) {
                                                    case 'Signature':
                                                        foreach ($value1->children($namespaces['ds']) as $key2 => $value2) {
                                                            switch ($key2) {
                                                                case 'SignedInfo':
                                                                    foreach ($value2->children($namespaces['ds']) as $key3 => $value3) {
                                                                        switch ($key3) {
                                                                            case 'Reference':
                                                                                foreach ($value3->children($namespaces['ds']) as $key4 => $value4) {
                                                                                    switch ($key4) {
                                                                                        case 'DigestValue':
                                                                                            LOG::info((String)$value4);
                                                                                            $cdr[$key][$ke][$k][$key1][$key2][$key3][$key4] = (string) $value4;
                                                                                            break;
                                                                                    }
                                                                                }
                                                                                break;
                                                                        }
                                                                        
                                                                    }
                                                                    break;   
                                                            }
                                                        }
                                                        break;
                                                }
                                                
                                            }
                                            break;
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        // READING HASH FROM CDR

        // foreach ($dataExt as $key => $value) {
        //     switch ($key) {
        //         case 'UBLExtensions':
        //             foreach ($value->children($namespaces['ext']) as $ke => $va) {
        //                 switch ($ke) {
        //                     case 'UBLExtension':
        //                         foreach ($va->children($namespaces['ext']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'ExtensionContent':
        //                                     foreach ($v->children() as $key1 => $value1) {
        //                                         LOG::info($key1."-".$value1);
        //                                         switch ($key1) {
        //                                             case 'Signature':
        //                                                 foreach ($value1->children() as $key2 => $value2) {
        //                                                     LOG::info($key2."-".$value2);
        //                                                     switch ($key2) {
        //                                                         case 'SignedInfo':
        //                                                             foreach ($value2->children() as $key3 => $value3) {
        //                                                                 LOG::info($key3."-".$value3);
        //                                                                 switch ($key3) {
        //                                                                     case 'Reference':
        //                                                                         foreach ($value3->children() as $key4 => $value4) {
        //                                                                             LOG::info($key4."-".$value4);
        //                                                                             switch ($key4) {
        //                                                                                 case 'DigestValue':
        //                                                                                     LOG::info((String)$value4);
        //                                                                                     break;
        //                                                                             }
        //                                                                         }
        //                                                                         break;
        //                                                                 }
                                                                        
        //                                                             }
        //                                                             break;   
        //                                                     }
        //                                                 }
        //                                                 break;
        //                                         }
                                                
        //                                     }
        //                                     // $cdr[$key][$ke][$k] = (string) $v;
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                 }
        //             }
        //             break;
        //     }
        // }

        // foreach ($dataCbc as $key => $value) {
        //     LOG::info($key."-".$value);
        //     switch ($key) {
        //         case 'UBLVersionID':
        //         case 'CustomizationID':
        //         case 'ID':
        //         case 'IssueDate':
        //         case 'IssueTime':
        //         case 'ResponseDate':
        //         case 'ResponseTime':
        //             $cdr[$key] = (string) $value;
        //             break;
        //         case 'Note':
        //             $cdr[$key][] = (string) $value;
        //             break;
        //     }
        // }

        // foreach ($dataCac as $key => $value) {
        //     switch ($key) {
        //         case 'SenderParty':
        //         case 'ReceiverParty':
        //         case 'DocumentResponse':
        //             foreach ($value->children($namespaces['cac']) as $ke => $va) {
        //                 switch ($ke) {
        //                     case 'PartyIdentification':
        //                     case 'Response':
        //                     case 'DocumentReference':
        //                         foreach ($va->children($namespaces['cbc']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'ID':
        //                                 case 'ReferenceID':
        //                                 case 'ResponseCode':
        //                                 case 'Description':
                                        
        //                                     $cdr[$key][$ke][$k] = (string) $v;
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                     case 'RecipientParty':
        //                         foreach ($va->children($namespaces['cac']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'PartyIdentification':
        //                                     foreach ($v->children($namespaces['cbc']) as $l => $b) {
        //                                         $cdr[$key][$ke][$k][$l] = (string) $b;
        //                                     }
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                 }
        //             }
        //             break;
        //     }
        // }

        return $cdr;
    }


    public function readSignDocumentCdr($path)
    {

        $response['status'] = 0;
        $response['code'] = '';
        $response['message'] = '';
        $message = '';

        // $path = 'C:\xampp1\htdocs\sisVentas\public\cdn/cdr\R-20536579746-01-F001-00004483.ZIP';
        LOG::info("readSignDocument--".$path);

        if(env('SYSTEM')=='linux')
            $nameExplode = explode('/', $path);
        elseif(env('SYSTEM')=='windows'){
            $nameExplode = explode('\\', $path);
            //TODO fix var $name
        }



        $name = end($nameExplode);
        $nameCdrExplode = explode('.', $name);
        $nameCdr = reset($nameCdrExplode) . '.XML';
        if(file_exists($path)){
            LOG::info("SI EXISTE EL ARCHIVO ZIP");
        }else{
            LOG::info("NO EXISTE EL ARCHIVO ZIP");
        }
        LOG::info("readSignDocument--".$nameCdr);

        \Zipper::make($path)->extractTo(storage_path('sunat/tmp'));
        $pathCdr = storage_path('sunat/tmp/' . $nameCdr);

        try {
            $cdr = $this->documentXMLCdr($pathCdr);

            # Grabar respuesta (CDR) en la base de Datos.
            // $docInvoiceCdr = DocInvoiceCdr::destroy($nIdInvoice);

            // $docInvoiceCdr = new DocInvoiceCdr();
            // $docInvoiceCdr->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdr->c_ubl_version_id = (isset($cdr['UBLVersionID'])) ? $cdr['UBLVersionID'] : null;
            // $docInvoiceCdr->c_customization_id = (isset($cdr['CustomizationID'])) ? $cdr['CustomizationID'] : null;
            // $docInvoiceCdr->c_id = $cdr['ID'];
            // $docInvoiceCdr->d_issue_date = $cdr['IssueDate'];
            // $docInvoiceCdr->d_issue_time = $cdr['IssueTime'];
            // $docInvoiceCdr->d_response_date = $cdr['ResponseDate'];
            // $docInvoiceCdr->d_response_time = $cdr['ResponseTime'];
            // $docInvoiceCdr->save();

            // $docInvoiceCdrSenderParty = new DocInvoiceCdrSenderParty();
            // $docInvoiceCdrSenderParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrSenderParty->c_party_identification_id = $cdr['SenderParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrSenderParty->save();

            // $docInvoiceCdrReceiverParty = new DocInvoiceCdrReceiverParty();
            // $docInvoiceCdrReceiverParty->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrReceiverParty->c_party_identification_id = $cdr['ReceiverParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrReceiverParty->save();

            // $docInvoiceCdrDocumentResponse = new DocInvoiceCdrDocumentResponse();
            // $docInvoiceCdrDocumentResponse->n_id_invoice = $nIdInvoice;
            // $docInvoiceCdrDocumentResponse->c_response_reference_id = $cdr['DocumentResponse']['Response']['ReferenceID'];
            // $docInvoiceCdrDocumentResponse->c_response_response_code = $cdr['DocumentResponse']['Response']['ResponseCode'];
    LOG::info("-------------------RESPUESTA XML-------------------");
    LOG::info($cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue']);
            // $docInvoiceCdrDocumentResponse->c_response_description = $cdr['DocumentResponse']['Response']['Description'];
            // $docInvoiceCdrDocumentResponse->c_document_reference_id = $cdr['DocumentResponse']['DocumentReference']['ID'];
            // $docInvoiceCdrDocumentResponse->c_recipient_party_party_identification_id = $cdr['DocumentResponse']['RecipientParty']['PartyIdentification']['ID'];
            // $docInvoiceCdrDocumentResponse->save();

            if (isset($cdr['Note'])) {
                foreach ($cdr['Note'] as $key => $value) {
                    $note = (string) $value;
                    $noteExplode = explode(' - ', $note);
                    // $docInvoiceCdrNote = new DocInvoiceCdrNote();
                    // $docInvoiceCdrNote->n_id_invoice = $nIdInvoice;
                    // $docInvoiceCdrNote->c_note = $note;
                    // $docInvoiceCdrNote->c_code = reset($noteExplode);
                    // $docInvoiceCdrNote->c_description = end($noteExplode);
                    // $docInvoiceCdrNote->save();
                }
            }

            // $docInvoiceCdrStatus = DocInvoiceCdrStatus::find($nIdInvoice);
            // switch ($cdr['DocumentResponse']['Response']['ResponseCode']) {
            //     case '0':
            //         $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
            //         if (isset($cdr['Note'])) {
            //             # Observado
            //             foreach ($cdr['Note'] as $key => $value) {
            //                 $response['message'][$key] = $value;
            //             }
            //             $message = implode(', ', $response['message']);
            //             // $docInvoiceCdrStatus->n_id_cdr_status = 3;
            //         } else {
            //             # Aceptado
            //             // $docInvoiceCdrStatus->n_id_cdr_status = 1;
            //             $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
            //             // LOG::info($cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue']);
            //             // $docInvoiceFile = DocInvoiceFile::find($nIdInvoice);
            //             // $docInvoiceFile->c_has_sunat_successfully_passed = 'yes';
            //             // $docInvoiceFile->save();
            //             $message = $response['message'];
            //         }
            //         break;
            //     default:
            //         # Rechazado
            //         $response['code'] = $cdr['DocumentResponse']['Response']['ResponseCode'];
            //         $response['message'] = $cdr['DocumentResponse']['Response']['Description'];
            //         $errErrorCode = ErrErrorCode::find($response['code']);
            //         $message = $errErrorCode->c_description;
            //         // $docInvoiceCdrStatus->n_id_cdr_status = 2;
            //         break;
            // }
            // $docInvoiceCdrStatus->save();
            $response['sign'] = $cdr['UBLExtensions']['UBLExtension']['ExtensionContent']['Signature']['SignedInfo']['Reference']['DigestValue'];
            $response['id'] = $cdr['ID'];
            $response['status'] = 1;

            // Log::info($message,
            //     [
            //     'lgph_id' => 6, 'n_id_invoice' => $nIdInvoice,
            //     'c_id_error_code' => $cdr['DocumentResponse']['Response']['ResponseCode'],
            //     'c_invoice_type_code' => $invoiceTypeCode,
            //     ]
            // );
        } catch (Exception $exc) {
            $response['message'] = $exc->getMessage();

            Log::error($response['message'],
                [
                'lgph_id' => 6, 'n_id_invoice' => '$nIdInvoice', 'c_invoice_type_code' => '$invoiceTypeCode',
                ]
            );
        }

        unlink($pathCdr);

        return $response;
    }

    public function documentXMLCdr($path)
    {
        if (!file_exists($path)) {
            throw new Exception('No existe el archivo XML del CDR.');
        }

        $cdr = [];

        $xml = simplexml_load_file($path, null, LIBXML_NOCDATA);
        LOG::info("--------------------LEENDO EL CDR--------------------");
        $namespaces = $xml->getNamespaces(true);
        $dataExt = $xml->children($namespaces['ext']);
        $dataCac = $xml->children($namespaces['cac']);
        $dataCbc = $xml->children($namespaces['cbc']);

        // READING HASH FROM INVOICE
        // foreach ($dataExt as $key => $value) {
        //     switch ($key) {
        //         case 'UBLExtensions':
        //             foreach ($value->children($namespaces['ext']) as $ke => $va) {
        //                 switch ($ke) {
        //                     case 'UBLExtension':
        //                         foreach ($va->children($namespaces['ext']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'ExtensionContent':
        //                                     foreach ($v->children($namespaces['ds']) as $key1 => $value1) {
        //                                         switch ($key1) {
        //                                             case 'Signature':
        //                                                 foreach ($value1->children($namespaces['ds']) as $key2 => $value2) {
        //                                                     switch ($key2) {
        //                                                         case 'SignedInfo':
        //                                                             foreach ($value2->children($namespaces['ds']) as $key3 => $value3) {
        //                                                                 switch ($key3) {
        //                                                                     case 'Reference':
        //                                                                         foreach ($value3->children($namespaces['ds']) as $key4 => $value4) {
        //                                                                             switch ($key4) {
        //                                                                                 case 'DigestValue':
        //                                                                                     LOG::info((String)$value4);
        //                                                                                     $cdr[$key][$ke][$k][$key1][$key2][$key3
        //                                                                                     ][$key4] = (string) $value4;
        //                                                                                     break;
        //                                                                             }
        //                                                                         }
        //                                                                         break;
        //                                                                 }
                                                                        
        //                                                             }
        //                                                             break;   
        //                                                     }
        //                                                 }
        //                                                 break;
        //                                         }
                                                
        //                                     }
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                 }
        //             }
        //             break;
        //     }
        // }

        // READING HASH FROM CDR

        foreach ($dataExt as $key => $value) {
            switch ($key) {
                case 'UBLExtensions':
                    foreach ($value->children($namespaces['ext']) as $ke => $va) {
                        switch ($ke) {
                            case 'UBLExtension':
                                foreach ($va->children($namespaces['ext']) as $k => $v) {
                                    switch ($k) {
                                        case 'ExtensionContent':
                                            foreach ($v->children() as $key1 => $value1) {
                                                LOG::info($key1."-".$value1);
                                                switch ($key1) {
                                                    case 'Signature':
                                                        foreach ($value1->children() as $key2 => $value2) {
                                                            LOG::info($key2."-".$value2);
                                                            switch ($key2) {
                                                                case 'SignedInfo':
                                                                    foreach ($value2->children() as $key3 => $value3) {
                                                                        LOG::info($key3."-".$value3);
                                                                        switch ($key3) {
                                                                            case 'Reference':
                                                                                foreach ($value3->children() as $key4 => $value4) {
                                                                                    LOG::info($key4."-".$value4);
                                                                                    switch ($key4) {
                                                                                        case 'DigestValue':
                                                                                            LOG::info((String)$value4);
                                                                                            $cdr[$key][$ke][$k][$key1][$key2][$key3][$key4] = (string) $value4;
                                                                                            break;
                                                                                    }
                                                                                }
                                                                                break;
                                                                        }
                                                                        
                                                                    }
                                                                    break;   
                                                            }
                                                        }
                                                        break;
                                                }
                                                
                                            }
                                            // $cdr[$key][$ke][$k] = (string) $v;
                                            break;
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        foreach ($dataCbc as $key => $value) {
            LOG::info($key."-".$value);
            switch ($key) {
                case 'UBLVersionID':
                case 'CustomizationID':
                case 'ID':
                $cdr[$key] = (string) $value;
                    break;
                case 'IssueDate':
                case 'IssueTime':
                case 'ResponseDate':
                case 'ResponseTime':
                    $cdr[$key] = (string) $value;
                    break;
                case 'Note':
                    $cdr[$key][] = (string) $value;
                    break;
            }
        }

        // foreach ($dataCac as $key => $value) {
        //     switch ($key) {
        //         case 'SenderParty':
        //         case 'ReceiverParty':
        //         case 'DocumentResponse':
        //             foreach ($value->children($namespaces['cac']) as $ke => $va) {
        //                 switch ($ke) {
        //                     case 'PartyIdentification':
        //                     case 'Response':
        //                     case 'DocumentReference':
        //                         foreach ($va->children($namespaces['cbc']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'ID':
        //                                 case 'ReferenceID':
        //                                 case 'ResponseCode':
        //                                 case 'Description':
                                        
        //                                     $cdr[$key][$ke][$k] = (string) $v;
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                     case 'RecipientParty':
        //                         foreach ($va->children($namespaces['cac']) as $k => $v) {
        //                             switch ($k) {
        //                                 case 'PartyIdentification':
        //                                     foreach ($v->children($namespaces['cbc']) as $l => $b) {
        //                                         $cdr[$key][$ke][$k][$l] = (string) $b;
        //                                     }
        //                                     break;
        //                             }
        //                         }
        //                         break;
        //                 }
        //             }
        //             break;
        //     }
        // }

        return $cdr;
    }


    public function pdfPrueba(){
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 028');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(0, PDF_MARGIN_TOP, 0);

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

        $pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');

        // set font
        $pdf->SetFont('times', 'B', 20);

        $pdf->AddPage('P', 'A4');
        $pdf->Cell(0, 0, 'A4 PORTRAIT', 1, 1, 'C');
        $pdf->AddPage('P', 'A4');

        // --- test backward editing ---

        $pdf->setPage(1, true);
        $pdf->SetY(0);
        $pdf->Cell(0, 0, 'A4 test', 1, 1, 'C');

        
        $pdf->setPage(2, true);
        $pdf->SetY(0);
        $pdf->Cell(0, 0, 'A4 PAGE2  test', 1, 1, 'C');

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output('example_028.pdf', 'I');

        //============================================================+
        // END OF FILE
        //============================================================+
    }
}