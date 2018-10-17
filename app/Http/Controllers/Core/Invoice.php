<?php
namespace sisVentas\Http\Controllers\Core;

use Illuminate\Support\Facades\Log;
use DOMDocument;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

use sisVentas\Persona;
use sisVentas\Articulo;

use Exception;
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
            $sunatRequest = $this->newSunatRequest('beta');
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
            $user = '20563817161MODDATOS';
            $password = 'MODDATOS';
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

    public static function buildInvoiceXml($idcliente,$tipo_comprobante,$serie_comprobante,$num_comprobante,$total_venta,$leyenda,$fecha,$hora,$idarticulo,$cantidad,$precio_venta)
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

        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = $serie_comprobante.'-'.$num_comprobante;
        $invoice->appendChild($cbcID);

        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = $fecha;
        $invoice->appendChild($issueDate);

        $issueTime = $dom->createElement('cbc:IssueTime');
        $issueTime->nodeValue = $hora;
        $invoice->appendChild($issueTime);

        $dueDate = $dom->createElement('cbc:DueDate');
        $dueDate->nodeValue = '2018-05-02';
        $invoice->appendChild($dueDate);

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

        $note = $dom->createElement('cbc:Note');
        $languageLocaleID = $dom->createAttribute('languageLocaleID');
        $languageLocaleID->value='1000';
        $note->appendChild($languageLocaleID);
        // $note->nodeValue='SEISCIENTOS TREINTA Y DOS CON 50/ 100';
        $note->nodeValue=$leyenda;
        $invoice->appendChild($note);
        
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

        $lineCountNumeric = $dom->createElement('cbc:LineCountNumeric');
        $lineCountNumeric->nodeValue=count($idarticulo);
        $invoice->appendChild($lineCountNumeric);

        $orderReference = $dom->createElement('cac:OrderReference');
        $invoice->appendChild($orderReference);

        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue='0004-00028955';
        $orderReference->appendChild($cbcID);

        $despatchDocumentReference = $dom->createElement('cac:DespatchDocumentReference');
        $invoice->appendChild($despatchDocumentReference);

        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue='0003-00051304';
        $despatchDocumentReference->appendChild($cbcID);        

        $issueDate = $dom->createElement('cbc:IssueDate');
        $issueDate->nodeValue = '2018-04-02';
        $despatchDocumentReference->appendChild($issueDate);

        $documentTypeCode = $dom->createElement('cbc:DocumentTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName =$dom->createAttribute('listName');
        $listName->value='Tipo de Documento';
        $listURI = $dom->createAttribute('listURI');
        $listURI->value='urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01';
        $documentTypeCode->appendChild($listAgencyName);
        $documentTypeCode->appendChild($listName);
        $documentTypeCode->appendChild($listURI);
        $documentTypeCode->nodeValue = '09';
        $despatchDocumentReference->appendChild($documentTypeCode);

        $signature = $dom->createElement('cac:Signature');
        $invoice->appendChild($signature);

        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = 'IDSignSP';
        $signature->appendChild($cbcID);

        $signatoryParty = $dom->createElement('cac:SignatoryParty');
        $signature->appendChild($signatoryParty);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $signatoryParty->appendChild($partyIdentification);

        $cbcID = $dom->createElement('cbc:ID');
        $cbcID->nodeValue = '20563817161';
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $signatoryParty->appendChild($partyName);

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue = 'CORPORACION BJR IMPORT SUR S.A.C.';
        $partyName->appendChild($cbcName);

        $digitalSignatoreAttachment = $dom->createElement('cac:DigitalSignatureAttachment');
        $signature->appendChild($digitalSignatoreAttachment);

        $externalReference = $dom->createElement('cac:ExternalReference');
        $digitalSignatoreAttachment->appendChild($externalReference);

        $cbcURI = $dom->createElement('cbc:URI');
        $cbcURI->nodeValue = '#SignatureSP';
        $externalReference->appendChild($cbcURI);

        $accountSupplierParty = $dom->createElement('cac:AccountingSupplierParty');
        $invoice->appendChild($accountSupplierParty);

        $party = $dom->createElement('cac:Party');
        $accountSupplierParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

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
        $cbcID->nodeValue = '20563817161';
        $partyIdentification->appendChild($cbcID);

        $partyName = $dom->createElement('cac:PartyName');
        $party->appendChild($partyName);

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue='CORPORACION BJR PRUEBA';
        $partyName->appendChild($cbcName);

        $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        $party->appendChild($partyTaxScheme);

        $registrationName = $dom->createElement('cbc:RegistrationName');
        $aRegistrationName = $dom->createCDATASection('CORPORACION BJR IMPORT SUR S.A.C.');
        $registrationName->appendChild($aRegistrationName);
        $partyTaxScheme->appendChild($registrationName);

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
        $companyID->nodeValue = '20563817161';
        $partyTaxScheme->appendChild($companyID);

        $taxScheme = $dom->createElement('cac:TaxScheme');
        $partyTaxScheme->appendChild($taxScheme);

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
        $cbcID->nodeValue = '20563817161';
        $taxScheme->appendChild($cbcID);

        $partyLegalEntity = $dom->createElement('cac:PartyLegalEntity');
        $party->appendChild($partyLegalEntity);

        $registrationName = $dom->createElement('cbc:RegistrationName');
        $registrationName->nodeValue='CORPORACION BJR IMPORT SUR S.A.C.';
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

        $addressTypeCode = $dom->createElement('cbc:AddressTypeCode');
        $listAgencyName = $dom->createAttribute('listAgencyName');
        $listAgencyName->value='PE:SUNAT';
        $listName = $dom->createAttribute('listName');
        $listName->value='Establecimientos anexos';
        $addressTypeCode->appendChild($listAgencyName);
        $addressTypeCode->appendChild($listName);
        $addressTypeCode->nodeValue='0000';
        $registrationAddress->appendChild($addressTypeCode);

        $cityName = $dom->createElement('cbc:CityName');
        $cityName->nodeValue='LIMA';
        $registrationAddress->appendChild($cityName);

        $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        $countrySubentity->nodeValue = 'LIMA';
        $registrationAddress->appendChild($countrySubentity);

        $district = $dom->createElement('cbc:District');
        $district->nodeValue='LIMA';
        $registrationAddress->appendChild($district);

        $addressLine = $dom->createElement('cac:AddressLine');
        $registrationAddress->appendChild($addressLine);

        $line = $dom->createElement('cbc:Line');
        $line->nodeValue='CALLE ENRIQUE BARRON N° 1024 - URB SANTA BEATRIZ';
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

        $contact = $dom->createElement('cac:Contact');
        $party->appendChild($contact);

        $name = $dom->createElement('cbc:Name');
        $name->nodeValue='MIGUEL DELGADO';
        $contact->appendChild($name);

        $query = Persona::findOrFail($idcliente);

        $accountCustomerParty = $dom->createElement('cac:AccountingCustomerParty');
        $invoice->appendChild($accountCustomerParty);

        $party = $dom->createElement('cac:Party');
        $accountCustomerParty->appendChild($party);

        $partyIdentification = $dom->createElement('cac:PartyIdentification');
        $party->appendChild($partyIdentification);

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

        $cbcName = $dom->createElement('cbc:Name');
        $cbcName->nodeValue=$query->nombre;
        $partyName->appendChild($cbcName);

        $partyTaxScheme = $dom->createElement('cac:PartyTaxScheme');
        $party->appendChild($partyTaxScheme);

        $registrationName = $dom->createElement('cbc:RegistrationName');
        $registrationName->appendChild($dom->createCDATASection($query->nombre));
        $partyTaxScheme->appendChild($registrationName);

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

        $registrationName = $dom->createElement('cbc:RegistrationName');
        $registrationName->nodeValue=$query->nombre;
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
        $cbcID->nodeValue='010701';
        $registrationAddress->appendChild($cbcID);

        $cityName = $dom->createElement('cbc:CityName');
        $cityName->nodeValue='UTCUBAMBA';
        $registrationAddress->appendChild($cityName);

        $countrySubentity = $dom->createElement('cbc:CountrySubentity');
        $countrySubentity->nodeValue = 'AMAZONAS';
        $registrationAddress->appendChild($countrySubentity);

        $district = $dom->createElement('cbc:District');
        $district->nodeValue='BAGUA GRANDE';
        $registrationAddress->appendChild($district);

        $addressLine = $dom->createElement('cac:AddressLine');
        $registrationAddress->appendChild($addressLine);

        $line = $dom->createElement('cbc:Line');
        $line->nodeValue='JR. MARISCAL CASTILLA #397 ';
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

        $taxAmount = $dom->createElement('cbc:TaxAmount');
        $aTaxAmount = $dom->createAttribute('currencyID');
        $aTaxAmount->value = 'PEN';
        $taxAmount->appendChild($aTaxAmount);
        $taxAmount->nodeValue = $igv;
        $taxTotal->appendChild($taxAmount);

        //////////////////////////////////////////////////////

        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
        $taxTotal->appendChild($taxSubTotal);

        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $taxableAmount->appendChild($currencyID);
        $taxableAmount->nodeValue=$neto;
        $taxSubTotal->appendChild($taxableAmount);

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

        $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $lineExtensionAmount->appendChild($currencyID);
        $lineExtensionAmount->nodeValue=$neto;
        $legalMonetaryTotal->appendChild($lineExtensionAmount);

        $taxInclusiveAmount = $dom->createElement('cbc:TaxInclusiveAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $taxInclusiveAmount->appendChild($currencyID);
        $taxInclusiveAmount->nodeValue=$total_venta;
        $legalMonetaryTotal->appendChild($taxInclusiveAmount);

        $allowanceTotalAmount = $dom->createElement('cbc:AllowanceTotalAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $allowanceTotalAmount->appendChild($currencyID);
        $allowanceTotalAmount->nodeValue='0.00';
        $legalMonetaryTotal->appendChild($allowanceTotalAmount);

        $chargeTotalAmount = $dom->createElement('cbc:ChargeTotalAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $chargeTotalAmount->appendChild($currencyID);
        $chargeTotalAmount->nodeValue='0.00';
        $legalMonetaryTotal->appendChild($chargeTotalAmount);

        $payableAmount = $dom->createElement('cbc:PayableAmount');
        $currencyID = $dom->createAttribute('currencyID');
        $currencyID->value='PEN';
        $payableAmount->appendChild($currencyID);
        $payableAmount->nodeValue=$total_venta;
        $legalMonetaryTotal->appendChild($payableAmount);

        //INVOICE LINE 1

        $cont = 1;
		while($cont <= count($idarticulo)){

			$neto_item = round(($precio_venta[$cont-1]/1.18),2);
        	$impuesto = $precio_venta[$cont-1]-$neto_item;

			$invoiceLine = $dom->createElement('cac:InvoiceLine');
	        $invoice->appendChild($invoiceLine);

	        $cbcID = $dom->createElement('cbc:ID');
	        $cbcID->nodeValue = $cont;
	        $invoiceLine->appendChild($cbcID);

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

	        $lineExtensionAmount = $dom->createElement('cbc:LineExtensionAmount');
	        $aLineExtensionAmount = $dom->createAttribute('currencyID');
	        $aLineExtensionAmount->value = 'PEN';
	        $lineExtensionAmount->appendChild($aLineExtensionAmount);
	        $lineExtensionAmount->nodeValue = $neto_item;
	        $invoiceLine->appendChild($lineExtensionAmount);

	        $pricingReference = $dom->createElement('cac:PricingReference');
	        $invoiceLine->appendChild($pricingReference);

	        $alternativeConditionPrice = $dom->createElement('cac:AlternativeConditionPrice');
	        $pricingReference->appendChild($alternativeConditionPrice);

	        $priceAmount = $dom->createElement('cbc:PriceAmount');
	        $aPriceAmount = $dom->createAttribute('currencyID');
	        $aPriceAmount->value = 'PEN';
	        $priceAmount->appendChild($aPriceAmount);
	        $priceAmount->nodeValue = $precio_venta[$cont-1];
	        $alternativeConditionPrice->appendChild($priceAmount);

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

	        $taxAmount = $dom->createElement('cbc:TaxAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxAmount->appendChild($aTaxAmount);
	        $taxAmount->nodeValue = $impuesto;
	        $taxTotal->appendChild($taxAmount);

	        $taxSubTotal = $dom->createElement('cac:TaxSubtotal');
	        $taxTotal->appendChild($taxSubTotal);

	        $taxableAmount = $dom->createElement('cbc:TaxableAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxableAmount->appendChild($aTaxAmount);
	        $taxableAmount->nodeValue = $neto_item;
	        $taxSubTotal->appendChild($taxableAmount);

	        $taxAmount = $dom->createElement('cbc:TaxAmount');
	        $aTaxAmount = $dom->createAttribute('currencyID');
	        $aTaxAmount->value='PEN';
	        $taxAmount->appendChild($aTaxAmount);
	        $taxAmount->nodeValue = $impuesto;
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

	        $cbcPercent = $dom->createElement('cbc:Percent');
	        $cbcPercent->nodeValue='18.00';
	        $taxCategory->appendChild($cbcPercent);

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

	        $description = $dom->createElement('cbc:Description');
	        $description->nodeValue = $articulo->nombre;
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
	        $priceAmount->nodeValue=$precio_venta[$cont-1];
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
        $xmlName = "20563817161-".$tipo_comprobante."-".$serie_comprobante."-".$num_comprobante;
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


    public function crearPDF(){
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false,false);

        $pdf->setPrintFooter(false);
        $pdf->SetTopMargin(63.23889);
        $pdf->SetLeftMargin(5);
        $pdf->SetRightMargin(5);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetCellPadding(0.5);

        # Logo
        $logo = public_path('/static/images/logo_pdf_a4_default.jpg');
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
        $pdf->MultiCell(74, 0, 'CAL. AGUSTIN DE LA TORRE GONZALES NRO. 194', 'L', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);
        $pdf->SetX($x);
        $pdf->MultiCell(74, 0, 'SAN ISIDRO LIMA PERU', 'L', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);

        $pdf->SetX($x);
        $pdf->MultiCell(74, 4, 'Tel.: ' . '721-2783', 'L', 'L');
         
        $pdf->SetX($x);
        $pdf->MultiCell(74, 4, 'E-mail: ' . 'www.avanzasoluciones.com.pe', 'L', 'L');
        
        # Descripción del documento
        $pdf->SetXY(140, 5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(65, 7, 'R.U.C. ' . '20524719585', 'LTR', 'C', false, 1, '', '', true, 0, false,
            true, 7, 'M');
        $pdf->Ln(0);
        $pdf->SetX(140);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(65, 8, 'FACTURA ELECTRÓNICA', 'LR', 'C', false, 1, '', '', true, 0, false, true, 8, 'M');
        $pdf->Ln(0);
        $pdf->SetX(140);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(65, 7, 'FF11' . ' Nº ' . '00000001', 'LRB', 'C', false, 1, '',
            '', true, 0, false, true, 7, 'M');

        # Detalle del cliente
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(5, 29);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(27, 4, 'SEÑOR (TITULAR)', 1, 'L', true, 1, '', '', true, 0, false, false, $rhm);
        $pdf->SetXY(32, $y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 4, 'MAESTRO PERU S.A.', 'TR', 'L', false, 1, '', '', true, 0, false,
            false, $rhm);
        $pdf->SetXY(5, 33);
        $y = $pdf->GetY();
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(27, 4, 'DIRECCIÓN', 1, 'L', true);
        $pdf->SetXY(32, $y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 4, 'JR. SAN LORENZO N° 881 SURQUILLO-LIMA', 'R', 'L', false, 1, '', '', true, 0, false, false,$rhm);
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
        $pdf->MultiCell(100, 4, '20112273922', 'RB', 'L');
        
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
        $pdf->MultiCell(23, 4, '01/10/2016', 'R', 'L');
        $pdf->SetXY(140, 37);
        $y = $pdf->GetY();
        $pdf->MultiCell(32, 4, 'FECHA VENCIMIENTO', 'BL', 'L');
        $pdf->SetXY(172, $y);
        $pdf->MultiCell(10, 4, ':', 'B', 'C');
        $pdf->SetXY(182, $y);
        $pdf->MultiCell(23, 4, '31/10/2016', 'BR', 'L');
        
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
        $pdf->MultiCell(32, 4, '20112273922', 1, 'C', false, 0);
        $pdf->MultiCell(28, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(35, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(40, 4, '', 1, 'C', false, 0);
        $pdf->MultiCell(40, 4, 'A 30 Días', 1, 'C', false, 0);
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

        $item = new stdClass();
        $item->codigo = "101";
        $item->descripcion = "LICENCIA DE USO DEL MÓDULO DE COMPRAS";
        $item->cantidad = "100.00";
        $item->precio = '100.00';
        $item->total = '100.00';
        $array->append($item);


        $item = new stdClass();
        $item->codigo = "102";
        $item->descripcion = "LICENCIA DE USO DEL MÓDULO DE VENTAS";
        $item->cantidad = "100.00";
        $item->precio = '100.00';
        $item->total = '100.00';
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);
        $array->append($item);

        $array2 = $array;

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
        
            $append[] = 'TRESCIENTOS CINCUENTICUATRO Y 00/100 SOLES';
        
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
        $pdf->MultiCell(30, 4, '0.00', 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(30, 4, '', 1, 'R');
        $pdf->SetXY($x + 30, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, '', 1, 'R');
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(20, 4, '54.00', 1, 'R');
        $pdf->SetXY($x + 20, $y);
        $x = $pdf->GetX();
        $pdf->MultiCell(40, 4, '354.00', 1, 'R');

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
        
        $pdf->write2DBarcode('irving123456', 'QRCODE,L', $x + 180, $y, 20);        








        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        $pdf->Output('example_001.pdf', 'I');

        //============================================================+
        // END OF FILE
        //============================================================+


    }


    public function readCdr($nIdInvoice, $path, $invoiceTypeCode)
    {

        $response['status'] = 0;
        $response['code'] = '';
        $response['message'] = '';
        $message = '';

        $path = 'C:\xampp1\htdocs\sisVentas\public\cdn/cdr\R-20563817161-01-F001-00004483.ZIP';

        if(env('SYSTEM')=='linux')
            $nameExplode = explode('/', $path);
        elseif(env('SYSTEM')=='windows'){
            $nameExplode = explode('\\', $path);
            //TODO fix var $name
        }

        $name = end($nameExplode);
        $nameCdrExplode = explode('.', $name);
        $nameCdr = reset($nameCdrExplode) . '.XML';

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


}