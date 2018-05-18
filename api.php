<?php

require_once "./config.php";


redirectIfGuest();

//Incoming Parameters 
$jsonData 	= getIncomingJson();


//***************************************************
// Get all Available locations from DB
 
if($jsonData['action'] == "getLocations") {


	$locations = (new Purolator\Origin())->getAll();

	echo json_encode($locations);
	exit;


//***************************************************
// Get Sender details by location ID

} elseif($jsonData['action'] == "getSenderLocation") {


	$id = !empty($jsonData['Id']) ? $jsonData['Id'] : getAdminLocationID(DEFAULT_LOCATION_ID);
	$location = (new Purolator\Origin())->getById($id);

    echo json_encode(array('sender' => $location));
    exit;



//***************************************************
// Get Receiver details by Order ID

} elseif($jsonData['action'] == "getReceiverByOrderId") {


	$receiver = (new Purolator\Customer())->getByOrderId($jsonData['orderID']);
   	echo json_encode(array('receiver' => $receiver));
   	exit;



//***************************************************
// Get Sender details by Order ID

} elseif($jsonData['action'] == "getSenderByOrderId") {

	$location = (new Purolator\Origin())->getByOrderId($jsonData['orderID']);
    echo json_encode(array('sender' => $location));
   	exit;



//***************************************************
// Get Packages details by Order ID

} elseif($jsonData['action'] == "getPackagesByOrderId") {

	$packages = (new Purolator\Shipment())->getPackagesByOrderId($jsonData['orderID']);
    echo json_encode(array('packages' => $packages));
   	exit;




//***************************************************
// Get Available Services (Purolator SOAP->GetFullEstimate)

} elseif($jsonData['action'] == "getAvalableServices") {


	$Estimate = new Purolator\Estimate($jsonData);
	$Estimate->get();

	echo json_encode(array(
						'services' => $Estimate->services, 
						'errors' => $Estimate->errors
					));
	exit;


//***************************************************
// Get Available Shipping Boxes

} elseif($jsonData['action'] == "getShippingBoxes") {


	$Shipment = new Purolator\Shipment($jsonData);

	echo json_encode(array(
						'boxes' => $Shipment->getShippingBoxes(), 
						'errors' => $Shipment->errors
					));
	exit;





//***************************************************
// Create Shipment (Purolator SOAP->CreateShipment)

} elseif($jsonData['action'] == "createShipment") {


	$Shipment = new Purolator\Shipment($jsonData);
	$Shipment->create();
	$Shipment->store();

	echo json_encode(array(
						'pins' => $Shipment->pins, 
						'errors' => $Shipment->errors
					));
	exit;




//***************************************************
// Void Shipment by Tracking PIN

} elseif($jsonData['action'] == "voidShipment") {
	

	if(empty($jsonData['pin'])) {
		exit;
	}

	$Shipment = new Purolator\Shipment($jsonData);
	$Shipment->void();

	echo json_encode(array(
						'voided' => $Shipment->voided, 
						'errors' => $Shipment->errors
					));
	exit;




//***************************************************
// Get Shipment Label (Purolator SOAP->GetDocuments)

} elseif($jsonData['action'] == "printLabel") {

	$Document = new Purolator\Document($jsonData);
	$Document->getLabel(); 

	echo json_encode(array(
						'pdfUrl' => $Document->pdfUrl, 
						'errors' => $Document->errors
					));



//***************************************************
// Write Shipping labels on the Server

} elseif($jsonData['action'] == "storeLabelOnServer") {

    $labelFile = "./labels/" . $jsonData['fileName'];
    $labelUrl = $jsonData['pdfUrl'];

    echo file_put_contents($labelFile, fopen($labelUrl, 'r'));





//***************************************************
// Get Manifest (Purolator SOAP->GetShipmentManifestDocument)

} elseif($jsonData['action'] == "getManifest") {

	//Consolidation should be used as an end of day process
	$Shipment = new Purolator\Shipment($jsonData);
	$Shipment->consolidate();

	//Once consolidation completed, the manifest can be produced
	$Document = new Purolator\Document($jsonData);
	$Document->getManifest(); 


	echo json_encode(array(
						'pdfUrl' => $Document->pdfUrl, 
						'manifestType' => $Document->manifestType,
						'manifestDescription' => $Document->manifestDescription, 
						'manifestStatus' => $Document->manifestStatus,  
						'errors' => $Document->errors
					));
	exit;



//***************************************************
// Create Return Shipment
} elseif($jsonData['action'] == "createReturnShipment") {	


	$ReturnShipment = new Purolator\ReturnShipment($jsonData);
	$ReturnShipment->create();
	$ReturnShipment->store();
	

	echo json_encode(array(
						'pins' => $ReturnShipment->pins, 
						'errors' => $ReturnShipment->errors
					));
	exit;




//***************************************************
// Get All Shipments by selected Date from DB

} elseif($jsonData['action'] == "getShipmentsByDate" || getIncomingString('action') == 'getShipmentsByDate') {

	$date = (empty($jsonData['date']) || $jsonData['date'] === "Invalid date") ? date('Y-m-d') : $jsonData['date'];
	$Shipment = new Shipment();

	echo json_encode(array(
						'shipments' => $Shipment->getByDate($date), 
						'errors' => $Shipment->errors
					));
	exit;





//***************************************************
// Get Shipment Details from DB by Tracking Code

} elseif($jsonData['action'] == "getShipmentDetails") {

	$Shipment = new Purolator\Shipment();

	echo json_encode(array(
						'shipment' => $Shipment->getByTrackingNumber($jsonData['pin']), 
						'errors' => $Shipment->errors
					));
	exit;



//***************************************************
// Send Return Shipping Labes to Customer

} elseif($jsonData['action'] == "sendEmail") {

	if(empty($jsonData['receiverEmail']) || empty($jsonData['pdfLabels'])) {
		exit;
	}


    $SendMail = new SendMail;
    $SendMail->SenderName  = COMPANY_NAME;
    $SendMail->SenderEmail = ADMIN_EMAIL;
    $SendMail->Subject = "Return Shipment Label - " . $jsonData['receiverName'];
    $SendMail->Body = $jsonData['receiverEmailBody'];

    $SendMail->AddAttachments("./labels/" . $jsonData['pins'][0] . ".pdf");
    $SendMail->AddRecipients($jsonData['receiverEmail'], $jsonData['receiverEmail']);

    $emailSent = $SendMail->Send();  


	echo json_encode(array('sent' => !!$emailSent));
	exit;

} 