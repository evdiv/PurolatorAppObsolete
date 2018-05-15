<?php 


class Shipment {

	private $incomingData;
	private $client;
	private $request;
	private $response;	

	public $pins = array();
	public $errors = array();	
	public $voided = '';  	


	public function __construct($incomingData = '') {

		$this->incomingData = $incomingData;
		$this->client = $this->createPWSSOAPClient();

		$this->request = new stdClass();
		$this->response = new stdClass();
	}


	// Create new Shipment 
	public function create() {

		$this->populateOrigin();
		$this->populateCustomer();
		$this->populatePaymentInformation();
		$this->populatePickupInformation();
		$this->populateShipmentOptions();
		$this->populatePackage();

		try {
			$this->response = $this->client->CreateShipment($this->request);
		} catch(Exception $e) {

			$this->errors[] = "Exception:" . $e;
		}

		$this->getErrors();
		$this->getPins();
	}



	//Store Shipments in the DB
	public function store() {

		$adminID = !empty($_SESSION['AdminID']) ? $_SESSION['AdminID'] : 0;
		$orderID = !empty($this->incomingData['orderID']) ? $this->incomingData['orderID'] : '';
	
		$locationCode = !empty($this->incomingData['senderLocationCode']) ? $this->incomingData['senderLocationCode'] : '';
		$serviceID = !empty($this->incomingData['serviceID']) ? $this->incomingData['serviceID'] : '';


		$counter = 0;
		foreach ($this->pins as $pin) {

			$packageSQL = '';

			if(empty($pin)) {
				continue;
			}

			if(!empty($this->incomingData['packages'][$counter])) {
				$packageSQL = " Length = " . $this->incomingData['packages'][$counter]['length'] . ", ";
				$packageSQL .= " Width = " . $this->incomingData['packages'][$counter]['width'] . ", ";
				$packageSQL .= " Height = " . $this->incomingData['packages'][$counter]['height'] . ", ";
				$packageSQL .= " Weight = " . $this->incomingData['packages'][$counter]['weight'] . ", ";				
				$packageSQL .= " Reference = '" . $this->incomingData['packages'][$counter]['reference'] . "', ";				
				$packageSQL .= " Note = '" . $this->incomingData['packages'][$counter]['note'] . "', ";				
			}

			mysql_query("INSERT INTO TrackingInfo SET 
				OrderID = '" . $orderID . "', 
				TrackingCarrierID = 2, 
				TrackingCode = '" . $pin . "', 
				LocationCode = '" . $locationCode . "',  
				AdminID = " . $adminID . ", 
				" . $packageSQL . "
				CourierService = '" . $serviceID . "'");
		
			$counter++;
		}

    	mysql_query("INSERT INTO OrdersNotes (AdminID, OrderID, Note, NoteDate)
            VALUES ({$adminID}, {$orderID}, 'Shipment created', Now())"); 
	}




	// Void Existing Shipment
	public function void() {

        $this->request->PIN->Value = $this->incomingData['pin'];

        try {
            $this->response = $this->client->VoidShipment($this->request);

        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }

        $this->getErrors();
        $this->getVoided();
        $this->updateAsVoidedinDB();
	}



	//Consolidate Shipments before printing Manifest
	public function consolidate() {

		try {
			$this->response = $this->client->Consolidate($this->request);
		} catch(Exception $e) {

			$this->errors[] = "Exception:" . $e;
		}
	}


	//Get all Shipments by selected Date
	public function getByDate($date = '') {
		$shipments = array();

		if(empty($date)) {
			$this->errors[] = "'The Date can not be empty'";
		}

		//If it is Shipper display only orders that have been shipped from the shipper's location.
		$shipperLocationID = getShipperLocationID();
		$shipperLocationSQL = !empty($shipperLocationID) ? " AND l.LocationsID = " . $shipperLocationID . " " : "";


		$result = mysql_query("SELECT t.*, l.LocationsID FROM TrackingInfo AS t, Locations AS l 
								WHERE t.LocationCode = l.LocationCode
								" . $shipperLocationSQL . "
								AND t.TrackingCarrierID = 2
								AND DATE(t.DateAdded) = '" . $date . "'
								ORDER BY t.OrderID DESC, t.TrackingCode");

		if($result) {
			while($row = mysql_fetch_assoc($result)) {

				$shipments[] = array(

					'Id' => $row['TrackingInfoID'],
					'orderId' => $row['OrderID'],
					'locationId' => $row['LocationsID'],
					'pin' => $row['TrackingCode'],
					'date' => $row['DateAdded'],
					'void' => $row['Void'],
					'locationCode' => $row['LocationCode']
				);
			}
		} else {
			$this->errors[] = 'Can not find Shipment for the Selected Date';
		}

		return $shipments;
	}



	//Get Shipment details by Tracking Number
	public function getByTrackingNumber($pin = '') {

		$shipment = array();


		if(empty($pin)) {
			$this->errors[] = "'Purolator PIN can not be empty'";
		}

		$result = mysql_query("SELECT t.*, a.Username, l.ActualCityName, l.SteetAddress, l.PostalCode, l.LocationsID  
							FROM TrackingInfo AS t
							LEFT JOIN Admin AS a
							ON t.AdminID = a.AdminID
							LEFT JOIN Locations AS l
							ON t.LocationCode = l.LocationCode
							
							WHERE t.TrackingCode =  '" . $pin . "'
							LIMIT 1");

		if($result) {
			$row = mysql_fetch_assoc($result);

			$shipment['date'] = $row['DateAdded'];
			$shipment['orderId'] = $row['OrderID'];
			$shipment['service'] = $row['CourierService'];
			$shipment['adminId'] = $row['AdminID'];
			$shipment['adminName'] = $row['Username'];
			$shipment['senderLocationId'] = $row['LocationsID'];
			$shipment['senderCity'] = $row['ActualCityName'];
			$shipment['senderAddress'] = $row['SteetAddress'];
			$shipment['senderPostalCode'] = $row['PostalCode'];
			$shipment['voided'] = $row['Void'];
		
		} else {

			$this->errors[] = 'Can not find Shipment Details for this Purolator PIN';
		}

		return $shipment;
	}


	public function getPackagesByOrderId($id) {
		$packages = array();

		$result = mysql_query("SELECT t.Length, t.Width, t.Height, t.Weight, t.Reference, t.Note
								FROM TrackingInfo AS t
								WHERE t.TrackingCarrierID = 2 
								AND t.Length IS NOT NULL 
								AND t.Width IS NOT NULL 
								AND t.Height IS NOT NULL
								AND t.Weight IS NOT NULL 
								AND t.OrderID = '" . $id ."'");

		if($result) {
			while($row = mysql_fetch_assoc($result)) {

				$packages[] = array(
					'length' => $row['Length'],
					'width' => $row['Width'],
					'height' => $row['Height'],
					'weight' => $row['Weight'],
					'reference' => $row['Reference'],
					'note' => $row['Note']
				);
			}
		}

		return $packages;
	}



	public function getShippingBoxes() {
		$boxes = array();

		$result = mysql_query("SELECT * FROM ProductsBoxes");

		if($result) {
			while($row = mysql_fetch_assoc($result)) {
				$boxes[] = array(
					'id' => $row['ProductsBoxesID'],
					'description' => $row['Description'],
					'weightLimit' => $row['WeightLimit'],
					'length' => $row['Length'],
					'width' => $row['Width'],
					'height' => $row['Height']
				);
			}
		}

		return $boxes;
	}



	private function createPWSSOAPClient() {

  		$client = new SoapClient( "./wsdl/ShippingService.wsdl", 
  								array	(
                                    'trace'		=>	true,
                                    'location'	=>	APP_PUROLATOR_SHIPMENT_URL,
                                    'uri'		=>	"http://purolator.com/pws/datatypes/v1",
                                    'login'		=>	APP_PUROLATOR_KEY, 
                                    'password'	=>	APP_PUROLATOR_PASS 
                                )
                        	);

	  	$headers[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v1', 
	                                'RequestContext', 
                                array (
                                        'Version'    =>  '1.6',
                                        'Language'   =>  'en',
                                        'GroupID'    =>  'xxx',
                                        'RequestReference'  =>  'Shipping Example'
                                    )
	                            ); 

  		$client->__setSoapHeaders($headers);

  		return $client;
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


    private function getVoided() {
        $responseArray = json_decode(json_encode($this->response), true);

        if(empty($responseArray)) {
            return;
        }

        try {
            $this->voided = $this->response->ShipmentVoided;
        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }
    }



	private function updateAsVoidedinDB() {

		if(empty($this->voided)) {
			return;
		}

		mysql_query("UPDATE TrackingInfo SET  Void = 1 WHERE TrackingCode = '" . $this->incomingData['pin'] . "' LIMIT 1");
	}



	private function populateOrigin() {

		$this->request->Shipment->SenderInformation->Address->Name = COMPANY_NAME;
		$this->request->Shipment->SenderInformation->Address->Company = COMPANY_NAME;
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


	private function populateCustomer() {

		$this->request->Shipment->ReceiverInformation->Address->Name = $this->incomingData['receiverName'];
		$this->request->Shipment->ReceiverInformation->Address->StreetNumber = $this->incomingData['receiverStreetNumber'];
		$this->request->Shipment->ReceiverInformation->Address->StreetName = $this->incomingData['receiverStreetName'];
		$this->request->Shipment->ReceiverInformation->Address->StreetAddress2 = $this->incomingData['receiverAddress2'];
		$this->request->Shipment->ReceiverInformation->Address->StreetAddress3 = $this->incomingData['receiverAddress3'];		
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
		$this->request->Shipment->ShowAlternativeServicesIndicator = "true";
	}



	private function populateShipmentOptions() {

		//Future Dated Shipments - YYYY-MM-DD format
		//$this->request->Shipment->ShipmentDate = "YOUR_SHIPMENT_DATE_HERE";

		$this->request->Shipment->TrackingReferenceInformation->Reference1 = $this->incomingData['orderID'];

		//Shipment Notes / Special Instructions (optional)
		$this->request->Shipment->OtherInformation->SpecialInstructions = $this->incomingData['specialInstructions'];

		// Define Proactive Notification Email details (optional)
		// $this->request->Shipment->ProactiveNotification->RequestorName = "MyName";
		// $this->request->Shipment->ProactiveNotification->RequestorEmail = "test@test.com ";
		// $this->request->Shipment->ProactiveNotification->Subscriptions->Subscription->Name = "MyName";
		// $this->request->Shipment->ProactiveNotification->Subscriptions->Subscription->Email = "test@test.com";
		// $this->request->Shipment->ProactiveNotification->Subscriptions->Subscription->NotifyWhenExceptionOccurs = "true";
		// $this->request->Shipment->ProactiveNotification->Subscriptions->Subscription->NotifyWhenDeliveryOccurs = "true";


		//ResidentialSignatureDomestic
		$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "ResidentialSignatureDomestic";
		$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

		//ResidentialSignatureIntl
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "ResidentialSignatureIntl";
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

		//OriginSignatureNotRequired
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->ID = "OriginSignatureNotRequired";
		//$this->request->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair->Value = "true";

		//Define the Shipment Document Type
		$this->request->PrinterType = 'Thermal';
	}


	private function populatePackage() {

		//Populate the Package Information
		$this->request->Shipment->PackageInformation->TotalWeight->Value = $this->incomingData['totalWeight'];
		$this->request->Shipment->PackageInformation->TotalWeight->WeightUnit = "kg";
		$this->request->Shipment->PackageInformation->TotalPieces = $this->incomingData['totalPieces'];
		$this->request->Shipment->PackageInformation->ServiceID = $this->incomingData['serviceID'];;

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

}
