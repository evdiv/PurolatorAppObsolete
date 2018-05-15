<?php


//*************************************
//Main Configuration

define("APP_NAME", "Your Company Purolator web client");
define("APP_URL", "/purolator");
define("COMPANY_NAME", "Company Name");
define("DEFAULT_LOCATION_ID", "1");


//************************************
//DB Connection

define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');


//************************************
//Purolator Credentials

define("APP_PUROLATOR_KEY", ""); 
define("APP_PUROLATOR_PASS", ""); 
define("APP_PUROLATOR_BILLING_ACCOUNT", "");
define("APP_PUROLATOR_FREIGHT_ACCOUNT", "");


define("APP_PUROLATOR_ESTIMATING_URL", "https://webservices.purolator.com/PWS/V1/Estimating/EstimatingService.asmx");
define("APP_PUROLATOR_SHIPMENT_URL", "https://webservices.purolator.com/PWS/V1/Shipping/ShippingService.asmx");
define("APP_PUROLATOR_SHIPMENT_DOCUMENTS_URL", "https://webservices.purolator.com/PWS/V1/ShippingDocuments/ShippingDocumentsService.asmx");
define("APP_PUROLATOR_RETURN_SHIPMENT_URL", "https://webservices.purolator.com/EWS/V2/ReturnsManagement/ReturnsManagementService.asmx");




//****************************************
// Require Classes and helper functions

require_once "./db.php"; 

require_once "./classes/Estimate.class.php";
require_once "./classes/Document.class.php";
require_once "./classes/Shipment.class.php";
require_once "./classes/ReturnShipment.class.php";
require_once "./classes/Origin.class.php";
require_once "./classes/Customer.class.php";

require_once "./helpers.php"; 



//******************************************
// Incoming Parameters

$ordersID = ForceIncomingInt('OrdersID', 0);
$returnShipment = ForceIncomingInt('ReturnShipment', 0);

