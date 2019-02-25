<?php
namespace sisVentas\Http\Controllers\Core;

use Illuminate\Support\Facades\Log;
use DOMDocument;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

use sisVentas\Persona;
use sisVentas\Articulo;
use DB;

use Exception;
use stdClass;
use ArrayObject;

use TCPDF;
class CreditNote
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
            $response['message'] = $exc->getMessage();

            Log::error($response['message'],
                [
                'lgph_id' => 5, 'n_id_invoice' => $id, 'c_invoice_type_code' => $nameExplode[1]
                ]
            );
        }
        
    }

    public function newSunatRequest($sunatServer)
    {

        
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
                'exceptions' => false,
            ];
            $client = new \SoapClient($wsdl, $options);

            $header = new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
                'Security', $authValues, false);
            $client->__setSoapHeaders($header);
            $response['status'] = 1;
            $response['client'] = $client;
            
        } catch (Exception $exc) {
            $response['message'] = $exc->getMessage();
        }

        $sunatRequest = $response;
        $client = $sunatRequest['client'];
        $params['ticket'] = '1535337237396';
        $getStatus = $client->__soapCall('getStatus', array($params));
        
        //dd($getStatus);
        return $response;
    }


    public static function buildCreditNoteXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta,$empresa,$documentoReferencia){
        $dom = new DOMDocument('1.0', 'UTF-8');
        #$dom->preserveWhiteSpace = false;
        // $dom->xmlStandalone = false;
        $dom->formatOutput = true;

        $creditNote = $dom->createElement('CreditNote');
        $newNode = $dom->appendChild($creditNote);
        $newNode->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
        $newNode->setAttribute('xmlns:cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $newNode->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $newNode->setAttribute('xmlns:ccts', 'urn:un:unece:uncefact:documentation:2');
        $newNode->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $newNode->setAttribute('xmlns:ext',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $newNode->setAttribute('xmlns:qdt', 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2');
        $newNode->setAttribute('xmlns:sac',
            'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1');
        $newNode->setAttribute('xmlns:udt',
            'urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2');
        $newNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $ublExtensions = $dom->createElement('ext:UBLExtensions');
        $creditNote->appendChild($ublExtensions);

        $ublExtension = $dom->createElement('ext:UBLExtension');
        $ublExtensions->appendChild($ublExtension);

        $extensionContent = $dom->createElement('ext:ExtensionContent');
        $ublExtension->appendChild($extensionContent);



        $ublVersionID = $dom->createElement('cbc:UBLVersionID');
        $ublVersionID->nodeValue = '2.1';
        $creditNote->appendChild($ublVersionID);

        $customizationID = $dom->createElement('cbc:CustomizationID');
        $customizationID->nodeValue = '2.0';
        $creditNote->appendChild($customizationID);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $creditNote->appendChild($cbcID);

        #VARIABLE
        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = $fecha;
        $creditNote->appendChild($issueDate);

        #VARIABLE
        $issueTime = $dom->createElement('cbc:IssueTime');
        $issueTime->nodeValue=$hora;
        $creditNote->appendChild($issueTime);

        #VARIABLE
        // $note = $dom->createElement('cbc:Note');
        // $nNote = $dom->createAttribute('languageLocaleID');
        // $nNote->value = '1000';
        // $note->appendChild($nNote);
        // $note->nodeValue = $leyenda;
        // $creditNote->appendChild($note);

        $documentCurrencyCode = $dom->createElement('cbc:DocumentCurrencyCode');
        $documentCurrencyCode->nodeValue='PEN';
        $creditNote->appendChild($documentCurrencyCode);

        $discrepancyResponse = $dom->createElement('cac:DiscrepancyResponse');
        $creditNote->appendChild($discrepancyResponse);

        #VARIABLE
        $referenceID = $dom->createElement('cbc:ReferenceID');
        $referenceID->nodeValue= $documentoReferencia['smodifica'].'-'.$documentoReferencia['nmodifica'];
        $discrepancyResponse->appendChild($referenceID);

        #VARIABLE
        $responseCode = $dom->createElement('cbc:ResponseCode');
        $responseCode->nodeValue=$documentoReferencia['motivo'];
        $discrepancyResponse->appendChild($responseCode);

        #VARIABLE
        $description = $dom->createElement('cbc:Description');
        $adescripcion = $dom->createCDATASection($documentoReferencia['motivod']);
        $description->appendChild($adescripcion);
        $discrepancyResponse->appendChild($description);

        $billingReference = $dom->createElement('cac:BillingReference');
        $creditNote->appendChild($billingReference);

        $invoiceDocumentReference = $dom->createElement('cac:InvoiceDocumentReference');
        $billingReference->appendChild($invoiceDocumentReference);

        #VARIABLE
        $cbcID =$dom->createElement('cbc:ID');
        $cbcID->nodeValue = $documentoReferencia['smodifica'].'-'.$documentoReferencia['nmodifica'];
        $invoiceDocumentReference->appendChild($cbcID);

        #VARIABLE
        $documentTypeCode = $dom->createElement('cbc:DocumentTypeCode');
        $documentTypeCode->nodeValue = $documentoReferencia['tipodoc'];
        $invoiceDocumentReference->appendChild($documentTypeCode);

        $signature = $dom->createElement('cac:Signature');
        $creditNote->appendChild($signature);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
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
        $acbcName = $dom->createCDATASection($empresa->razon_social);
        $cbcName->appendChild($acbcName);
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
        $creditNote->appendChild($accountSupplierParty);

        $party = $dom->createElement('cac:Party');
        $accountSupplierParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue=$empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $acbcName = $dom->createCDATASection($empresa->nombre_comercial);
        $cbcName->appendChild($acbcName);
        $partyName->appendChild($cbcName);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aregistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aregistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $registrationAdress = $dom->createElement('cac:RegistrationAddress');
        $partyLegalEntity->appendChild($registrationAdress);

        #VARIABLE
        $addressTypeCode = $dom->createElement('cbc:AddressTypeCode');
        $addressTypeCode->nodeValue='0001';
        $registrationAdress->appendChild($addressTypeCode);

        
        ////////////////////////////////////////////////////////////////////////////////////////

        $query = Persona::findOrFail($idcliente);

        $accountCustomerParty = $dom->createElement('cac:AccountingCustomerParty');
        $creditNote->appendChild($accountCustomerParty);

        $party = $dom->createElement('cac:Party');
        $accountCustomerParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue=$query->num_documento;
        $partyIdentification->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aregistrationName =$dom->createCDATASection($query->nombre);
        $registrationName->appendChild($aregistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $neto = round(($total_venta/1.18),2);
        $igv = $total_venta-$neto;

        $taxTotal = $dom->createElement('cac:TaxTotal');
        $creditNote->appendChild($taxTotal);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxTotal->appendChild($taxAmount);

        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        $taxTotal->appendChild($taxSubTotal);

        #VARIABLE
        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        $aTaxableAmount = $dom->createAttribute('currencyID');
        $aTaxableAmount->value = 'PEN';
        $taxableAmount->appendChild($aTaxableAmount);
        $taxableAmount->nodeValue = $neto;
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

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $taxCategory->appendChild($taxScheme);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $acbcID = $dom->createAttribute('schemeID');
        $acbcID->value='UN/ECE 5153';
        $schemeAgencyID = $dom->createAttribute('schemeAgencyID');
        $schemeAgencyID->value='6';
        $cbcID->appendChild($acbcID);
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
        $creditNote->appendChild($legalMonetaryTotal);

        #VARIABLE
        $payableAmount = $dom->createElement('cbc:PayableAmount');
        $aPayableAmount = $dom->createAttribute('currencyID');
        $aPayableAmount->value = 'PEN';
        $payableAmount->appendChild($aPayableAmount);
        $payableAmount->nodeValue = number_format(round($total_venta,2),2,'.','');
        $legalMonetaryTotal->appendChild($payableAmount);

        //CREDITNOTELINE 1

        $cont = 1;
        while($cont <= count($idarticulo)){

            $total_item = $precio_venta[$cont-1]*$cantidad[$cont-1];

            $neto_item = $total_item/1.18;
            $impuesto = $total_item-$neto_item;

            $creditNoteLine = $dom->createElement('cac:CreditNoteLine');
            $creditNote->appendChild($creditNoteLine);

            $cbcID = $dom->createElement('cbc:ID');
            $cbcID->nodeValue = $cont;
            $creditNoteLine->appendChild($cbcID);

            #VARIABLE
            $creditedQuantity = $dom->createElement('cbc:CreditedQuantity');
            $aCreditedQuantity = $dom->createAttribute('unitCode');
            $aCreditedQuantity->value = 'NIU';
            $creditedQuantity->appendChild($aCreditedQuantity);
            $creditedQuantity->nodeValue = $cantidad[$cont-1];
            $creditNoteLine->appendChild($creditedQuantity);

            #VARIABLE
            $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
            $aLineExtensionAmount = $dom->createAttribute('currencyID');
            $aLineExtensionAmount->value = 'PEN';
            $lineExtensionAmount->appendChild($aLineExtensionAmount);
            $lineExtensionAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
            $creditNoteLine->appendChild($lineExtensionAmount);

            $pricingReference = $dom->createElement('cac:PricingReference');
            $creditNoteLine->appendChild($pricingReference);

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
            $priceTypeCode->nodeValue = '01';
            $alternativeConditionPrice->appendChild($priceTypeCode);

            $taxTotal = $dom->createElement('cac:TaxTotal');
            $creditNoteLine->appendChild($taxTotal);

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
            $aTaxableAmount = $dom->createAttribute('currencyID');
            $aTaxableAmount->value='PEN';
            $taxableAmount->appendChild($aTaxableAmount);
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
            $percent = $dom->createElement('cbc:Percent');
            $percent->nodeValue = '18.00';
            $taxCategory->appendChild($percent);

            #VARIABLE
            $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
            $taxExemptionReasonCode->nodeValue = '10';
            $taxCategory->appendChild($taxExemptionReasonCode);

            $taxScheme = $dom->createElement('cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $cbcID = $dom->createElement('cbc:ID');
            $cbcID->nodeValue = '1000';
            $taxScheme->appendChild($cbcID);

            $cbcName = $dom->createElement('cbc:Name');
            $cbcName->nodeValue = 'IGV';
            $taxScheme->appendChild($cbcName);

            $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
            $taxTypeCode->nodeValue = 'VAT';
            $taxScheme->appendChild($taxTypeCode);

            $item = $dom->createElement('cac:Item');
            $creditNoteLine->appendChild($item);

            $articulo = Articulo::findOrFail($idarticulo[$cont-1]);

            #VARIABLE
            $description = $dom->createElement('cbc:Description');
            $adescripcion = $dom->createCDATASection($articulo->nombre);
            $description->appendChild($adescripcion);
            $item->appendChild($description);

            // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
            // $item->appendChild($sellersItemIdentification);

            #VARIABLE
            // $cbcID = $dom->createElement('cbc:ID');
            // $cbcID->nodeValue='GLG199';
            // $sellersItemIdentification->appendChild($cbcID);

            // $comodityClassification = $dom->createElement('cac:CommodityClassification');
            // $item->appendChild($comodityClassification);

            // $itemClassficationCode = $dom->createElement('cbc:ItemClassificationCode');
            // $listID = $dom->createAttribute('listID');
            // $listID->value='UNSPSC';
            // $listAgencyName = $dom->createAttribute('listAgencyName');
            // $listAgencyName->value='GS1 US';
            // $listName = $dom->createAttribute('listName');
            // $listName->value='Item Classification';
            // $itemClassficationCode->appendChild($listID);
            // $itemClassficationCode->appendChild($listAgencyName);
            // $itemClassficationCode->appendChild($listName);
            // $itemClassficationCode->nodeValue='45111723';
            // $comodityClassification->appendChild($itemClassficationCode);

            $price = $dom->createElement('cac:Price');
            $creditNoteLine->appendChild($price);

            #VARIABLE
            $priceAmount = $dom->createElement('cbc:PriceAmount');
            $aPriceAmount = $dom->createAttribute('currencyID');
            $aPriceAmount->value = 'PEN';
            $priceAmount->appendChild($aPriceAmount);
            $priceAmount->nodeValue = number_format(round($precio_venta[$cont-1],2),2,'.','');
            $price->appendChild($priceAmount);
            $cont++;
        }

        



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
        // if (!file_exists($privateKey))
        //     throw new Exception('No se encuentra la LLAVE PRIVADA');
        // if (!file_exists($publicKey))
        //     throw new Exception('No se encuentra la LLAVE PUBLICA');

        // $ReferenceNodeName = 'ExtensionContent';

        // // Load the XML to be signed
        // $doc = new DOMDocument();
        // $doc->load($xmlFullPath);

        // // Create a new Security object
        // $objDSig = new XMLSecurityDSig();
        // // Use the c14n exclusive canonicalization
        // $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        // // Sign using SHA-256
        // $objDSig->addReference(
        //     $doc, 
        //     XMLSecurityDSig::SHA1, 
        //     array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
        //     $options = array('force_uri' => true)
        // );

        // // Create a new (private) Security key
        // $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
        // /*
        // If key has a passphrase, set it using
        // $objKey->passphrase = '<passphrase>';
        // */

        // // Load the private key
        // $objKey->loadKey($privateKey, TRUE);
        // //$objKey->loadKey('certificates/PrivateKey.key', TRUE);

        // // Sign the XML file
        // $objDSig->sign($objKey,$doc->getElementsByTagName($ReferenceNodeName)->item(1));

        // // Add the associated public key to the signature
        // $objDSig->add509Cert(file_get_contents($publicKey));
        // //$objDSig->add509Cert(file_get_contents('certificates/ServerCertificate.cer'));
        // // Append the signature to the XML
        // //die(var_dump($doc->documentElement));
        // $objDSig->appendSignature($doc->getElementsByTagName($ReferenceNodeName)->item(1));
        // //$objDSig->appendSignature($ReferenceNodeName);
        // // Save the signed XML
        // $doc->save($xmlFullPath);


        $privateKey = storage_path('sunat/keys/PrivateKey.key');
        $publicKey = storage_path('sunat/keys/ServerCertificate.cer');
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
        // LOG::info('-------------------------REFERENCE NODE-------------------------'.$doc->getElementsByTagName($ReferenceNodeName)->item(1));
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

    public static function buildDebitNoteXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta,$empresa,$documentoReferencia){
        $dom = new DOMDocument('1.0', 'UTF-8');
        #$dom->preserveWhiteSpace = false;
        // $dom->xmlStandalone = false;
        $dom->formatOutput = true;

        $debitNote = $dom->createElement('DebitNote');
        $newNode = $dom->appendChild($debitNote);
        $newNode->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2');
        $newNode->setAttribute('xmlns:cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $newNode->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $newNode->setAttribute('xmlns:ccts', 'urn:un:unece:uncefact:documentation:2');
        $newNode->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $newNode->setAttribute('xmlns:ext',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $newNode->setAttribute('xmlns:qdt', 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2');
        $newNode->setAttribute('xmlns:sac',
            'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1');
        $newNode->setAttribute('xmlns:udt',
            'urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2');
        $newNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $ublExtensions = $dom->createElement('ext:UBLExtensions');
        $debitNote->appendChild($ublExtensions);

        $ublExtension = $dom->createElement('ext:UBLExtension');
        $ublExtensions->appendChild($ublExtension);

        $extensionContent = $dom->createElement('ext:ExtensionContent');
        $ublExtension->appendChild($extensionContent);



        $ublVersionID = $dom->createElement('cbc:UBLVersionID');
        $ublVersionID->nodeValue = '2.1';
        $debitNote->appendChild($ublVersionID);

        $customizationID = $dom->createElement('cbc:CustomizationID');
        $customizationID->nodeValue = '2.0';
        $debitNote->appendChild($customizationID);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $debitNote->appendChild($cbcID);

        #VARIABLE
        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = $fecha;
        $debitNote->appendChild($issueDate);

        #VARIABLE
        $issueTime = $dom->createElement('cbc:IssueTime');
        $issueTime->nodeValue=$hora;
        $debitNote->appendChild($issueTime);

        #VARIABLE
        // $note = $dom->createElement('cbc:Note');
        // $nNote = $dom->createAttribute('languageLocaleID');
        // $nNote->value = '1000';
        // $note->appendChild($nNote);
        // $note->nodeValue = $leyenda;
        // $debitNote->appendChild($note);

        $documentCurrencyCode = $dom->createElement('cbc:DocumentCurrencyCode');
        $documentCurrencyCode->nodeValue='PEN';
        $debitNote->appendChild($documentCurrencyCode);

        $discrepancyResponse = $dom->createElement('cac:DiscrepancyResponse');
        $debitNote->appendChild($discrepancyResponse);

        #VARIABLE
        $referenceID = $dom->createElement('cbc:ReferenceID');
        $referenceID->nodeValue=$documentoReferencia['smodifica'].'-'.$documentoReferencia['nmodifica'];
        $discrepancyResponse->appendChild($referenceID);

        #VARIABLE
        $responseCode = $dom->createElement('cbc:ResponseCode');
        $responseCode->nodeValue=$documentoReferencia['motivo'];
        $discrepancyResponse->appendChild($responseCode);

        #VARIABLE
        $description = $dom->createElement('cbc:Description');
        $adescripcion = $dom->createCDATASection($documentoReferencia['motivod']);
        $description->appendChild($adescripcion);
        $discrepancyResponse->appendChild($description);

        $billingReference = $dom->createElement('cac:BillingReference');
        $debitNote->appendChild($billingReference);

        $invoiceDocumentReference = $dom->createElement('cac:InvoiceDocumentReference');
        $billingReference->appendChild($invoiceDocumentReference);

        #VARIABLE
        $cbcID =$dom->createElement('cbc:ID');
        $cbcID->nodeValue = $documentoReferencia['smodifica'].'-'.$documentoReferencia['nmodifica'];
        $invoiceDocumentReference->appendChild($cbcID);

        #VARIABLE
        $documentTypeCode = $dom->createElement('cbc:DocumentTypeCode');
        $documentTypeCode->nodeValue = $documentoReferencia['tipodoc'];
        $invoiceDocumentReference->appendChild($documentTypeCode);

        $signature = $dom->createElement('cac:Signature');
        $debitNote->appendChild($signature);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
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
        $acbcName = $dom->createCDATASection($empresa->razon_social);
        $cbcName->appendChild($acbcName);
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
        $debitNote->appendChild($accountSupplierParty);

        $party = $dom->createElement('cac:Party');
        $accountSupplierParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue=$empresa->ruc;
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        #VARIABLE
        $cbcName = $dom->createElement('cbc:Name');
        $acbcName = $dom->createCDATASection($empresa->nombre_comercial);
        $cbcName->appendChild($acbcName);
        $partyName->appendChild($cbcName);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aregistrationName = $dom->createCDATASection($empresa->razon_social);
        $registrationName->appendChild($aregistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $registrationAdress = $dom->createElement('cac:RegistrationAddress');
        $partyLegalEntity->appendChild($registrationAdress);

        #VARIABLE
        $addressTypeCode = $dom->createElement('cbc:AddressTypeCode');
        $addressTypeCode->nodeValue='0001';
        $registrationAdress->appendChild($addressTypeCode);

        
        ////////////////////////////////////////////////////////////////////////////////////////

        $query = Persona::findOrFail($idcliente);

        $accountCustomerParty = $dom->createElement('cac:AccountingCustomerParty');
        $debitNote->appendChild($accountCustomerParty);

        $party = $dom->createElement('cac:Party');
        $accountCustomerParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $schemeID = $dom->createAttribute('schemeID');
        $schemeID->value='6';
        $schemeName = $dom->createAttribute('schemeName');
        $schemeName->value='SUNAT:Identificador de Documento de Identidad';
        $schemAgencyName = $dom->createAttribute('schemeAgencyName');
        $schemAgencyName->value='PE:SUNAT';
        $schemeURI = $dom->createAttribute('schemeURI');
        $schemeURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06';
        $cbcID->appendChild($schemeID);
        $cbcID->appendChild($schemeName);
        $cbcID->appendChild($schemAgencyName);
        $cbcID->appendChild($schemeURI);
        $cbcID->nodeValue=$query->num_documento;
        $partyIdentification->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        #VARIABLE
        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aregistrationName =$dom->createCDATASection($query->nombre);
        $registrationName->appendChild($aregistrationName);
        $partyLegalEntity->appendChild($registrationName);

        $neto = round(($total_venta/1.18),2);
        $igv = $total_venta-$neto;        

        $taxTotal = $dom->createElement('cac:TaxTotal');
        $debitNote->appendChild($taxTotal);

        #VARIABLE
        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxTotal->appendChild($taxAmount);

        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        $taxTotal->appendChild($taxSubTotal);

        #VARIABLE
        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        $aTaxableAmount = $dom->createAttribute('currencyID');
        $aTaxableAmount->value = 'PEN';
        $taxableAmount->appendChild($aTaxableAmount);
        $taxableAmount->nodeValue = $neto;
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

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $taxCategory->appendChild($taxScheme);

        #VARIABLE
        $cbcID = $dom->createElement('cbc:ID');
        $acbcID = $dom->createAttribute('schemeID');
        $acbcID->value='UN/ECE 5153';
        $schemeAgencyID = $dom->createAttribute('schemeAgencyID');
        $schemeAgencyID->value='6';
        $cbcID->appendChild($acbcID);
        $cbcID->appendChild($schemeAgencyID);
        $cbcID->nodeValue = '1000';
        $taxScheme->appendChild($cbcID);

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = 'IGV';
        $taxScheme->appendChild($cbcName);

        $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
        $taxTypeCode->nodeValue = 'VAT';
        $taxScheme->appendChild($taxTypeCode);

        $legalMonetaryTotal = $dom->createElement('cac:RequestedMonetaryTotal');
        $debitNote->appendChild($legalMonetaryTotal);

        #VARIABLE
        $payableAmount = $dom->createElement('cbc:PayableAmount');
        $aPayableAmount = $dom->createAttribute('currencyID');
        $aPayableAmount->value = 'PEN';
        $payableAmount->appendChild($aPayableAmount);
        $payableAmount->nodeValue = number_format(round($total_venta,2),2,'.','');
        $legalMonetaryTotal->appendChild($payableAmount);

        //debitNoteLINE 1
        $cont = 1;
        while($cont <= count($idarticulo)){

            $total_item = $precio_venta[$cont-1]*$cantidad[$cont-1];

            $neto_item = $total_item/1.18;
            $impuesto = $total_item-$neto_item;

            $debitNoteLine = $dom->createElement('cac:DebitNoteLine');
            $debitNote->appendChild($debitNoteLine);

            #VARIABLE
            $cbcID = $dom->createElement('cbc:ID');
            $cbcID->nodeValue = $cont;
            $debitNoteLine->appendChild($cbcID);

            #VARIABLE
            $debitedQuantity = $dom->createElement('cbc:DebitedQuantity');
            $aDebitedQuantity = $dom->createAttribute('unitCode');
            $aDebitedQuantity->value = 'NIU';
            $debitedQuantity->appendChild($aDebitedQuantity);
            $debitedQuantity->nodeValue = $cantidad[$cont-1];
            $debitNoteLine->appendChild($debitedQuantity);

            #VARIABLE
            $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
            $aLineExtensionAmount = $dom->createAttribute('currencyID');
            $aLineExtensionAmount->value = 'PEN';
            $lineExtensionAmount->appendChild($aLineExtensionAmount);
            $lineExtensionAmount->nodeValue = number_format(round($neto_item,2),2,'.','');
            $debitNoteLine->appendChild($lineExtensionAmount);

            $pricingReference = $dom->createElement('cac:PricingReference');
            $debitNoteLine->appendChild($pricingReference);

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
            $priceTypeCode->nodeValue = '01';
            $alternativeConditionPrice->appendChild($priceTypeCode);

            $taxTotal = $dom->createElement('cac:TaxTotal');
            $debitNoteLine->appendChild($taxTotal);

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
            $aTaxableAmount = $dom->createAttribute('currencyID');
            $aTaxableAmount->value='PEN';
            $taxableAmount->appendChild($aTaxableAmount);
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
            $percent = $dom->createElement('cbc:Percent');
            $percent->nodeValue = '18.00';
            $taxCategory->appendChild($percent);

            #VARIABLE
            $taxExemptionReasonCode = $dom->createElement('cbc:TaxExemptionReasonCode');
            $taxExemptionReasonCode->nodeValue = '10';
            $taxCategory->appendChild($taxExemptionReasonCode);

            $taxScheme = $dom->createElement('cac:TaxScheme');
            $taxCategory->appendChild($taxScheme);

            $cbcID = $dom->createElement('cbc:ID');
            $cbcID->nodeValue = '1000';
            $taxScheme->appendChild($cbcID);

            $cbcName = $dom->createElement('cbc:Name');
            $cbcName->nodeValue = 'IGV';
            $taxScheme->appendChild($cbcName);

            $taxTypeCode = $dom->createElement('cbc:TaxTypeCode');
            $taxTypeCode->nodeValue = 'VAT';
            $taxScheme->appendChild($taxTypeCode);

            $item = $dom->createElement('cac:Item');
            $debitNoteLine->appendChild($item);

            $articulo = Articulo::findOrFail($idarticulo[$cont-1]);

            #VARIABLE
            $description = $dom->createElement('cbc:Description');
            $adescripcion = $dom->createCDATASection($articulo->nombre);
            $description->appendChild($adescripcion);
            $item->appendChild($description);

            // $sellersItemIdentification = $dom->createElement('cac:SellersItemIdentification');
            // $item->appendChild($sellersItemIdentification);

            // $cbcID = $dom->createElement('cbc:ID');
            // $cbcID->nodeValue='GLG199';
            // $sellersItemIdentification->appendChild($cbcID);

            // $comodityClassification = $dom->createElement('cac:CommodityClassification');
            // $item->appendChild($comodityClassification);

            // $itemClassficationCode = $dom->createElement('cbc:ItemClassificationCode');
            // $listID = $dom->createAttribute('listID');
            // $listID->value='UNSPSC';
            // $listAgencyName = $dom->createAttribute('listAgencyName');
            // $listAgencyName->value='GS1 US';
            // $listName = $dom->createAttribute('listName');
            // $listName->value='Item Classification';
            // $itemClassficationCode->appendChild($listID);
            // $itemClassficationCode->appendChild($listAgencyName);
            // $itemClassficationCode->appendChild($listName);
            // $itemClassficationCode->nodeValue='45111723';
            // $comodityClassification->appendChild($itemClassficationCode);

            $price = $dom->createElement('cac:Price');
            $debitNoteLine->appendChild($price);

            #VARIABLE
            $priceAmount = $dom->createElement('cbc:PriceAmount');
            $aPriceAmount = $dom->createAttribute('currencyID');
            $aPriceAmount->value = 'PEN';
            $priceAmount->appendChild($aPriceAmount);
            $priceAmount->nodeValue = number_format(round($precio_venta[$cont-1],2),2,'.','');
            $price->appendChild($priceAmount);
            $cont++;
        }

        



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
        // if (!file_exists($privateKey))
        //     throw new Exception('No se encuentra la LLAVE PRIVADA');
        // if (!file_exists($publicKey))
        //     throw new Exception('No se encuentra la LLAVE PUBLICA');

        // $ReferenceNodeName = 'ExtensionContent';

        // // Load the XML to be signed
        // $doc = new DOMDocument();
        // $doc->load($xmlFullPath);

        // // Create a new Security object
        // $objDSig = new XMLSecurityDSig();
        // // Use the c14n exclusive canonicalization
        // $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        // // Sign using SHA-256
        // $objDSig->addReference(
        //     $doc, 
        //     XMLSecurityDSig::SHA1, 
        //     array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
        //     $options = array('force_uri' => true)
        // );

        // // Create a new (private) Security key
        // $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
        // /*
        // If key has a passphrase, set it using
        // $objKey->passphrase = '<passphrase>';
        // */

        // // Load the private key
        // $objKey->loadKey($privateKey, TRUE);
        // //$objKey->loadKey('certificates/PrivateKey.key', TRUE);

        // // Sign the XML file
        // $objDSig->sign($objKey,$doc->getElementsByTagName($ReferenceNodeName)->item(1));

        // // Add the associated public key to the signature
        // $objDSig->add509Cert(file_get_contents($publicKey));
        // //$objDSig->add509Cert(file_get_contents('certificates/ServerCertificate.cer'));
        // // Append the signature to the XML
        // //die(var_dump($doc->documentElement));
        // $objDSig->appendSignature($doc->getElementsByTagName($ReferenceNodeName)->item(1));
        // //$objDSig->appendSignature($ReferenceNodeName);
        // // Save the signed XML
        // $doc->save($xmlFullPath);


        $privateKey = storage_path('sunat/keys/PrivateKey.key');
        $publicKey = storage_path('sunat/keys/ServerCertificate.cer');
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
        // LOG::info('-------------------------REFERENCE NODE-------------------------'.$doc->getElementsByTagName($ReferenceNodeName)->item(1));
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
        // $pdf->SetFont('helvetica', '', 12);
        if($cliente->tipo_comprobante=='01'){
            $pdf->MultiCell(65, 8, 'FACTURA ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        }
        if($cliente->tipo_comprobante=='03'){
            $pdf->MultiCell(65, 8, 'BOLETA ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        }
        if($cliente->tipo_comprobante=='07'){
            $pdf->MultiCell(65, 8, 'NOTA DE CRÉDITO ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        }
        if($cliente->tipo_comprobante=='08'){
            $pdf->MultiCell(65, 8, 'NOTA DE DÉBITO ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
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
        $pdf->MultiCell(200, 4, 'DOCUMENTO QUE MODIFICA', 1, 'C', true, 1);

        $pdf->Ln(0);
        $pdf->SetX(5);
        $y = $pdf->GetY();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(60, 4, 'TIPO DOCUMENTO', 1, 'C', false, 0);
        $pdf->MultiCell(40, 4, 'NÚMERO', 1, 'C', false, 0);
        $pdf->MultiCell(100, 4, 'MOTIVO', 1, 'C', false, 1);

        $pdf->Ln(0);
        $pdf->SetX(5);
        $pdf->SetTextColor(0, 0, 0);
        if($cliente->docmodifica_tipo=='01'){
            $pdf->MultiCell(60, 4, 'FACTURA ELECTRÓNICA', 1, 'C', false, 0);
        }
        if($cliente->docmodifica_tipo=='03'){
            $pdf->MultiCell(60, 4, 'BOLETA ELECTRÓNICA', 1, 'C', false, 0);
        }
        $pdf->MultiCell(40, 4, $cliente->docmodifica, 1, 'C', false, 0);
        $pdf->MultiCell(100, 4, $cliente->modifica_motivo.'-'.$cliente->modifica_motivod, 1, 'C', false, 1);        

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
        
            $append[] = '00098066799';
        
            $append[] = '';
        
            $append[] = '';
        
            $append[] = 'SON: '.$leyenda .' SOLES';
        
            $append[] = "S.E.U.O.";
        
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
        if($cliente->tipo_comprobante=='07'){
            $pdf->MultiCell(100, 0, 'Representación Impresa de la ' . "NOTA DE CRÉDITO ELECTRÓNICA", '', 'C');
        }
        if($cliente->tipo_comprobante=='08'){
            $pdf->MultiCell(100, 0, 'Representación Impresa de la ' . "NOTA DE DÉBITO ELECTRÓNICA", '', 'C');
        }
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