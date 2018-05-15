<?php 

class Estimate {

	private $incomingData;
	private $client;
	private $request;
	private $response;	

	public $services = array();
	public $errors = array();	


	public function __construct($incomingData = '') {

		$this->incomingData = $incomingData;
		$this->client = $this->createPWSSOAPClient();

		$this->request = new stdClass();
		$this->response = new stdClass();
	}


	public function get() {

		$this->getFullEstimate();

		$this->getErrors();
		$this->getServicesRates();
	}



	private function createPWSSOAPClient() {

  		$client = new SoapClient( "./wsdl/EstimatingService.wsdl", 
  								array (
                                    'trace'		=>	true,
                                    'location'	=>	APP_PUROLATOR_ESTIMATING_URL,
                                    'uri'		=>	"http://purolator.com/pws/datatypes/v1",
                                    'login'		=>	APP_PUROLATOR_KEY,
                                    'password'	=>	APP_PUROLATOR_PASS
                                )
                        	);

	  	$headers[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v1', 
	                                'RequestContext', 
	                                array (
                                        'Version'           =>  '1.4',
                                        'Language'          =>  'en',
                                        'GroupID'           =>  'xxx',
                                        'RequestReference'  =>  'Rating Example'
	                                )
	                            ); 

  		$client->__setSoapHeaders($headers);

  		return $client;
	}



	private function populateSender() {

		$this->request->Shipment->SenderInformation->Address->Name = COMPANY_NAME;
		$this->request->Shipment->SenderInformation->Address->StreetNumber = $this->incomingData['senderStreetNumber'];
		$this->request->Shipment->SenderInformation->Address->StreetName = $this->incomingData['senderStreetName'];
		$this->request->Shipment->SenderInformation->Address->City = $this->incomingData['senderCity'];
		$this->request->Shipment->SenderInformation->Address->Province = $this->incomingData['senderProvince'];
		$this->request->Shipment->SenderInformation->Address->Country = "CA";
		$this->request->Shipment->SenderInformation->Address->PostalCode = $this->incomingData['senderPostalCode'];   
		$this->request->Shipment->SenderInformation->Address->PhoneNumber->CountryCode = "1";
		$this->request->Shipment->SenderInformation->Address->PhoneNumber->AreaCode = $this->incomingData['senderPhoneAreaCode'];
		$this->request->Shipment->SenderInformation->Address->PhoneNumber->Phone = $this->incomingData['senderPhone'];
	}


	private function populateReceiver() {

		$this->request->Shipment->ReceiverInformation->Address->Name = $this->incomingData['receiverName'];
		$this->request->Shipment->ReceiverInformation->Address->StreetNumber = $this->incomingData['receiverStreetNumber'];
		$this->request->Shipment->ReceiverInformation->Address->StreetName = $this->incomingData['receiverStreetName'];
		$this->request->Shipment->ReceiverInformation->Address->City = $this->incomingData['receiverCity'];
		$this->request->Shipment->ReceiverInformation->Address->Province = $this->incomingData['receiverProvince'];
		$this->request->Shipment->ReceiverInformation->Address->Country = "CA";
		$this->request->Shipment->ReceiverInformation->Address->PostalCode = $this->incomingData['receiverPostalCode']; 
		$this->request->Shipment->ReceiverInformation->Address->PhoneNumber->CountryCode = "1";
		$this->request->Shipment->ReceiverInformation->Address->PhoneNumber->AreaCode = $this->incomingData['receiverPhoneAreaCode'];
		$this->request->Shipment->ReceiverInformation->Address->PhoneNumber->Phone = $this->incomingData['receiverPhone'];
	}


	private function populatePaymentInformation() {

		$this->request->Shipment->PaymentInformation->PaymentType = "Sender";
		$this->request->Shipment->PaymentInformation->BillingAccountNumber = APP_PUROLATOR_BILLING_ACCOUNT;
		$this->request->Shipment->PaymentInformation->RegisteredAccountNumber = APP_PUROLATOR_FREIGHT_ACCOUNT;
	}

	private function populatePickupInformation() {

		$this->request->Shipment->PickupInformation->PickupType = "DropOff";
		$this->request->ShowAlternativeServicesIndicator = "true";
	}


	private function populateShipmentOptions() {

		//Future Dated Shipments - YYYY-MM-DD format
		//$this->request->Shipment->ShipmentDate = "YOUR_SHIPMENT_DATE_HERE";

		//Define OptionsInformation
		//ResidentialSignatureDomestic
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "ResidentialSignatureDomestic";
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

		//ResidentialSignatureIntl
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "ResidentialSignatureIntl";
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

		//OriginSignatureNotRequired
		$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "OriginSignatureNotRequired";
		$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

	}


	private function populatePackage() {

		//Populate the Package Information
		$this->request->Shipment->PackageInformation->TotalWeight->Value = $this->incomingData['totalWeight'];
		$this->request->Shipment->PackageInformation->TotalWeight->WeightUnit = "kg";
		$this->request->Shipment->PackageInformation->TotalPieces = $this->incomingData['totalPieces'];
		$this->request->Shipment->PackageInformation->ServiceID = "PurolatorExpress";

		$counter = 0;
		foreach($this->incomingData['packages'] as $package){

			$weight = round($package['weight'], 0);
			if($weight < 1) { $weight = 1; }

			// weight
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Weight->Value = $weight;
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Weight->WeightUnit = "kg";
			// length
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Length->Value = $package['length'];
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Length->DimensionUnit = "cm";
			// width
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Width->Value = $package['width'];
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Width->DimensionUnit = "cm";
			// height
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Height->Value = $package['height'];
			$this->request->Shipment->PackageInformation->PiecesInformation->Piece[$counter]->Height->DimensionUnit = "cm";

			$counter++;
		}
	}


	private function getFullEstimate() {

		$this->populateSender();
		$this->populateReceiver();
		$this->populatePaymentInformation();
		$this->populatePickupInformation();
		$this->populateShipmentOptions();
		$this->populatePackage();

		try {
			$this->response = $this->client->GetFullEstimate($this->request);
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


	private function getServicesRates() {

		$responseArray = json_decode(json_encode($this->response), true);

		if(empty($responseArray)) {
			return;
		}


		$temp = $responseArray['ShipmentEstimates']['ShipmentEstimate']['Surcharges']['Surcharge'][0]['Type'];
		
		if($temp == 'BeyondDestination'){
			$TotalPrice = $responseArray['ShipmentEstimates']['ShipmentEstimate']['TotalPrice'];

			$this->services[] = array(
				'service_name' => 'Beyond Destination',
				'charge' => $TotalPrice);
			return;
		}


		foreach ($this->response->ShipmentEstimates->ShipmentEstimate as $serv) {
			$this->services[] = array(
            	'service_name' => $serv->ServiceID,
            	'charge' => $serv->TotalPrice);
		}

		//Sort Services with the cheapest first
		usort($this->services, function($a, $b) {
			if($a['charge'] == $b['charge']) {
				return 0;
			}
			return ($a['charge'] < $b['charge']) ? -1 : 1;
		});


		return count($this->services);
	}




}