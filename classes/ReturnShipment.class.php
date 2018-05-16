<?php 

namespace Purolator;

class ReturnShipment {

    private $db;

    private $incomingData;
    private $client; 
    private $request;
    public $response;  

    public $errors = array();
    public $pins = array();


    public function __construct($incomingData = '') {

        $this->db = new Database();

        $this->incomingData = $incomingData;
        $this->client = $this->createPWSSOAPClient(); 
        
        $this->request = new stdClass();
        $this->response = new stdClass();
    }


    public function create() {

        $this->CreateReturnShipment();
        $this->getErrors();
        $this->getPins();
    }


    public function store() {

        $adminID = !empty($_SESSION['AdminID']) ? $_SESSION['AdminID'] : 0;
        $orderID = !empty($this->incomingData['orderID']) ? $this->incomingData['orderID'] : '';
    
        $locationCode = !empty($this->incomingData['senderLocationCode']) ? $this->incomingData['senderLocationCode'] : '';
        $serviceID = !empty($this->incomingData['serviceID']) ? $this->incomingData['serviceID'] : '';

        $counter = 0;
        foreach ($this->pins as $pin) {

            if(empty($pin)) {
                continue;
            }

            $packageSQL = '';

            if(!empty($this->incomingData['packages'][$counter])) {
                $packageSQL = " Length = " . $this->incomingData['packages'][$counter]['length'] . ", ";
                $packageSQL .= " Width = " . $this->incomingData['packages'][$counter]['width'] . ", ";
                $packageSQL .= " Height = " . $this->incomingData['packages'][$counter]['height'] . ", ";
                $packageSQL .= " Weight = " . $this->incomingData['packages'][$counter]['weight'] . ", ";               
                $packageSQL .= " Reference = '" . $this->incomingData['packages'][$counter]['reference'] . "', ";               
                $packageSQL .= " Note = '" . $this->incomingData['packages'][$counter]['note'] . "', ";             
            }


            $this->db->query("INSERT INTO TrackingReturnsInfo SET 
                                OrderID = '" . $orderID . "', 
                                TrackingCarrierID = 2, 
                                TrackingCode = '" . $pin . "', 
                                LocationCode = '" . $locationCode . "',  
                                AdminID = " . $adminID . ", 
                                " . $packageSQL . "
                                CourierService = '" . $serviceID . "'");
            
            $counter++;
        }


        $shipmentNote = "Return shipment created. <a href=\'/purolator/labels/" . $this->pins[0] . ".pdf\' target=\'_blank\'>Download label</a>. ";

        $this->db->query("INSERT INTO OrdersNotes SET 
                            AdminID = " . $adminID . ", 
                            OrderID = '" . $orderID . "', 
                            Note = '" . $shipmentNote . "',
                            NoteDate = Now()");
    }


	private function createPWSSOAPClient() {

  		$client = new SoapClient( "./wsdl/ReturnsManagementService.wsdl", 
			array	(
                'trace'		=>	true,
                'location'	=>	APP_PUROLATOR_RETURN_SHIPMENT_URL,
                'uri'		=>	"http://purolator.com/pws/datatypes/v2", 
                'login'		=>	APP_PUROLATOR_KEY,
                'password'	=>	APP_PUROLATOR_PASS
            )
        );

	  	$headers[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v2',
	        'RequestContext', 
                array (
                    'Version'           =>  '2.0',
                    'Language'          =>  'en',
                    'GroupID'           =>  'xxx',
                    'RequestReference'  =>  'LM Return Shipment' 
                )
            ); 


  		$client->__setSoapHeaders($headers);
  		
  		return $client;
	}




    private function CreateReturnShipment() {

        $this->populateCustomer();
        $this->populateOrigin();
        $this->populatePaymentInformation();
        $this->populatePickupInformation();
        $this->populateShipmentOptions();
        $this->populatePackage();


        try {
            $this->response = $this->client->CreateReturnsManagementShipment($this->request);
        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }
    }


    private function getErrors(){

        foreach($this->response->ResponseInformation->Errors as $error){
            $this->errors[] = $error->Description;
        }

        return count($this->errors);
    }


    private function getPins() {

        $responseArray = json_decode(json_encode($this->response), true);

        if(empty($responseArray)) {
            return;
        }

        $pins = $this->response->PiecePINs->PIN;

        if(count($pins) > 1) {

            foreach($pins as $pin) {
                $this->pins[] = $pin->Value;
            }
        
        } else {

            $this->pins[] = $pins->Value;
        }

    }


    //In the Return Shipment Customer is a Sender
    private function populateCustomer() {

        $this->request->ReturnsManagementShipment->SenderInformation->Address->Name = $this->incomingData['receiverName'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->StreetNumber = $this->incomingData['receiverStreetNumber'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->StreetName = $this->incomingData['receiverStreetName'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->StreetAddress2 = $this->incomingData['receiverAddress2'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->StreetAddress3 = $this->incomingData['receiverAddress3'];       
        $this->request->ReturnsManagementShipment->SenderInformation->Address->City = $this->incomingData['receiverCity'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->Province = $this->incomingData['receiverProvince'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->Country = "CA";
        $this->request->ReturnsManagementShipment->SenderInformation->Address->PostalCode = $this->incomingData['receiverPostalCode'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->PhoneNumber->CountryCode = "1";
        $this->request->ReturnsManagementShipment->SenderInformation->Address->PhoneNumber->AreaCode = $this->incomingData['receiverPhoneAreaCode'];
        $this->request->ReturnsManagementShipment->SenderInformation->Address->PhoneNumber->Phone = $this->incomingData['receiverPhone'];
    }


    //In the Return Shipment Origin is a Receiver
    private function populateOrigin() {

        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->Name = COMPANY_NAME;
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->Company = COMPANY_NAME;
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->StreetNumber = $this->incomingData['senderStreetNumber'];
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->StreetName = $this->incomingData['senderStreetName'];
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->City = $this->incomingData['senderCity'];
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->Province = $this->incomingData['senderProvince'];
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->Country = "CA";
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->PostalCode = $this->incomingData['senderPostalCode']; 
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->PhoneNumber->CountryCode = "1";
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->PhoneNumber->AreaCode = $this->incomingData['senderPhoneAreaCode'];
        $this->request->ReturnsManagementShipment->ReceiverInformation->Address->PhoneNumber->Phone = $this->incomingData['senderPhone'];
    }



    private function populatePaymentInformation() {

        $this->request->ReturnsManagementShipment->PaymentInformation->PaymentType = "Sender";
        $this->request->ReturnsManagementShipment->PaymentInformation->BillingAccountNumber = APP_PUROLATOR_BILLING_ACCOUNT;
        $this->request->ReturnsManagementShipment->PaymentInformation->RegisteredAccountNumber = APP_PUROLATOR_FREIGHT_ACCOUNT;
    }



    private function populatePickupInformation() {

        $this->request->ReturnsManagementShipment->PickupInformation->PickupType = "DropOff";
    }



    private function populateShipmentOptions() {

        $this->request->RMA = $this->incomingData['orderID'];
        $this->request->ReturnsManagementShipment->TrackingReferenceInformation->Reference1 = $this->incomingData['orderID'];

        //Define ProactiveNotification Information
        // $this->request->ReturnsManagementShipment->ProactiveNotification->RequestorName = $this->incomingData['receiverName'];
        // $this->request->ReturnsManagementShipment->ProactiveNotification->RequestorEmail = $this->incomingData['receiverEmail'];;
        // $this->request->ReturnsManagementShipment->ProactiveNotification->Subscriptions->Subscription->Name = COMPANY_NAME;
        // $this->request->ReturnsManagementShipment->ProactiveNotification->Subscriptions->Subscription->Email = "websales@domain.com";
        // $this->request->ReturnsManagementShipment->ProactiveNotification->Subscriptions->Subscription->NotifyWhenExceptionOccurs = "true";
        // $this->request->ReturnsManagementShipment->ProactiveNotification->Subscriptions->Subscription->NotifyWhenDeliveryOccurs = "true ";

        $this->request->PrinterType = 'Thermal';
    }



    private function populatePackage() {

        //Populate the Package Information
        $this->request->ReturnsManagementShipment->PackageInformation->TotalWeight->Value = $this->incomingData['totalWeight'];
        $this->request->ReturnsManagementShipment->PackageInformation->TotalWeight->WeightUnit = "kg";
        $this->request->ReturnsManagementShipment->PackageInformation->TotalPieces = $this->incomingData['totalPieces'];
        $this->request->ReturnsManagementShipment->PackageInformation->ServiceID = $this->incomingData['serviceID'];

        $counter = 0;
        foreach($this->incomingData['packages'] as $package){

            $weight = round($package['weight'], 0);
            if($weight < 1) { $weight = 1; }

            // weight
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Weight->Value = $weight;
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Weight->WeightUnit = "kg";
            // length
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Length->Value = $package['length'];
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Length->DimensionUnit = "cm";
            // width
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Width->Value = $package['width'];
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Width->DimensionUnit = "cm";
            // height
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Height->Value = $package['height'];
            $this->request->ReturnsManagementShipment->PackageInformation->PiecesInformation->Piece[$counter]->Height->DimensionUnit = "cm";

            $counter++;
        }
    }

}