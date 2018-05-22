<?php

require_once "./config.php";


$db = new Purolator\Database();

$db->query("CREATE TABLE Admin (
							AdminID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							Username VARCHAR(150) NOT NULL,
							Email VARCHAR(100) NOT NULL,
							LocationsID INT(5)  NULL DEFAULT NULL,
							ShippingAccess INT(1)  NULL DEFAULT NULL,
							DateAdded TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)"
						);

$db->query( "INSERT INTO Admin (Username, Email, LocationsID, ShippingAccess) VALUES ('John', 'john@yourdomain.com', '1', '1'");
$db->query( "INSERT INTO Admin (Username, Email, LocationsID, ShippingAccess) VALUES ('Ken', 'ken@yourdomain.com', '1', '1'");        



$db->query("CREATE TABLE ProductsBoxes (
							ProductsBoxesID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							Description VARCHAR(250) NULL DEFAULT NULL,
							WeightLimit NUMERIC(10,2)  NULL DEFAULT NULL,
							BoxWeight NUMERIC(10,2)  NULL DEFAULT NULL,
							Length NUMERIC(10,2)  NULL DEFAULT NULL,
							Width NUMERIC(10,2)  NULL DEFAULT NULL,
							Height NUMERIC(10,2)  NULL DEFAULT NULL)"
						);

$db->query( "INSERT INTO ProductsBoxes (Description, WeightLimit, BoxWeight, Length, Width, Height) 
					VALUES ('Small box', 5.00, 0.30, 1.10, 1.20, 1.30");  
$db->query( "INSERT INTO ProductsBoxes (Description, WeightLimit, BoxWeight, Length, Width, Height) 
					VALUES ('Medium box', 10.00, 0.40, 1.90, 2.20, 2.10");   
$db->query( "INSERT INTO ProductsBoxes (Description, WeightLimit, BoxWeight, Length, Width, Height) 
					VALUES ('Large box', 15.00, 0.90, 2.20, 2.80, 2.20"); 					 					   



$db->query("CREATE TABLE TrackingInfo (
							TrackingInfoID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							OrderID INT(11)  NOT NULL,
							AdminID INT(11)  NULL DEFAULT 1, 
							TrackingCarrierID VARCHAR(250)  NULL DEFAULT 1,
							TrackingCode VARCHAR(250) NULL DEFAULT NULL,
							LocationCode VARCHAR(10) NULL DEFAULT NULL,
							CourierService VARCHAR(100) NULL DEFAULT NULL,
							Status INT(1)  NULL DEFAULT NULL,
							Void INT(1)  NULL DEFAULT NULL,
							Length NUMERIC(10,2)  NULL DEFAULT NULL,
							Width NUMERIC(10,2)  NULL DEFAULT NULL,
							Height NUMERIC(10,2)  NULL DEFAULT NULL,
							Weight NUMERIC(10,2)  NULL DEFAULT NULL,
							Reference VARCHAR(250) NULL DEFAULT NULL,
							Note VARCHAR(512) NULL DEFAULT NULL,
							DateAdded TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)"
						);


$db->query("CREATE TABLE TrackingReturnsInfo (
							TrackingReturnsInfoID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							OrderID INT(11)  NOT NULL,
							AdminID INT(11)  NULL DEFAULT 1, 
							TrackingCarrierID VARCHAR(250)  NULL DEFAULT 1,
							TrackingCode VARCHAR(250) NULL DEFAULT NULL,
							LocationCode VARCHAR(10) NULL DEFAULT NULL,
							CourierService VARCHAR(100) NULL DEFAULT NULL,
							Length NUMERIC(10,2)  NULL DEFAULT NULL,
							Width NUMERIC(10,2)  NULL DEFAULT NULL,
							Height NUMERIC(10,2)  NULL DEFAULT NULL,
							Weight NUMERIC(10,2)  NULL DEFAULT NULL,
							Reference VARCHAR(250) NULL DEFAULT NULL,
							Note VARCHAR(512) NULL DEFAULT NULL,
							DateAdded TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)"
						);


$db->query("CREATE TABLE OrdersNotes (
							OrderNotesID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							OrderID INT(11)  NOT NULL,
							AdminID INT(11)  NULL DEFAULT 1, 
							Note VARCHAR(512) NULL DEFAULT NULL,
							NoteDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)"
						);


$db->query("CREATE TABLE Locations (
							LocationsID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							City VARCHAR(250) NULL DEFAULT NULL,
							ProvincesID INT(11)  NOT NULL,
							Phone VARCHAR(50) NULL DEFAULT NULL,
							Fax VARCHAR(50) NULL DEFAULT NULL,
							Email VARCHAR(100) NULL DEFAULT NULL,
							SteetAddress VARCHAR(255) NULL DEFAULT NULL,
							PostalCode VARCHAR(255) NULL DEFAULT NULL,
							Active INT(1)  NULL DEFAULT NULL,
							Note VARCHAR(512) NULL DEFAULT NULL,
							DateAdded TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)"
						);

$db->query( "INSERT INTO Locations (City, ProvincesID, Phone, Email, SteetAddress, PostalCode) 
	VALUES ('Toronto', '1', '416-999-99-99', 'toronto@yourdomain.com', '120 Torresdale Avenue', 'M2R3N7'");   
$db->query( "INSERT INTO Locations (City, ProvincesID, Phone, Email, SteetAddress, PostalCode) 
	VALUES ('Pickering', '1', '416-555-55-55', 'pickering@yourdomain.com', '188 Kingston Rd', 'L1V1C9'");
$db->query( "INSERT INTO Locations (City, ProvincesID, Phone, Email, SteetAddress, PostalCode) 
	VALUES ('Markham', '1', '416-444-44-44', 'markham@yourdomain.com', '9832 Markham Rd', 'L6E0E5'");
$db->query( "INSERT INTO Locations (City, ProvincesID, Phone, Email, SteetAddress, PostalCode) 
	VALUES ('Winnipeg', '2', '416-222-22-22', 'winnipeg@yourdomain.com', '1841 Pembina Highway', 'R3T2G6'");



$db->query("CREATE TABLE Provinces (
							ProvincesID INT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							ProvinceName VARCHAR(100) NULL DEFAULT NULL,
							ProvinceCode VARCHAR(2) NULL DEFAULT NULL)"
						);

$db->query( "INSERT INTO Provinces (ProvinceName, ProvinceCode) VALUES ('Ontario', 'ON'");
$db->query( "INSERT INTO Provinces (ProvinceName, ProvinceCode) VALUES ('Manitoba', 'MB'");

