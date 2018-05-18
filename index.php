<?php

require_once "./config.php";

redirectIfGuest();

?>

<!doctype html>
<html lang="en">
	<head>
	    <!-- Required meta tags -->
	    <meta charset="utf-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	   	<title><?= APP_NAME ?></title>

	    <!-- Bootstrap CSS -->
	    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css">

		<!-- Date Picker -->
  		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha18/css/tempusdominus-bootstrap-4.min.css" />

	    <!-- Custom Styles-->
	    <link rel="stylesheet" href="./css/style.css">

		<!-- Font Awesome -->
	    <script src="https://use.fontawesome.com/eb8d3fb422.js"></script>

	</head>

	<body>

		<div id="app">

			<!-- Top Navigation -->
			<?php include "./views/top-navbar.php"; ?>
			<!--/ Top Navigation -->


			<!-- Modals -->
			<?php include "./views/modals/get-rates.php"; ?>
			<?php include "./views/modals/create-shipment.php"; ?>
			<?php include "./views/modals/print-label.php"; ?>
			<?php include "./views/modals/void-shipment.php"; ?>
			<?php include "./views/modals/print-manifest.php"; ?>
			<?php include "./views/modals/show-shipment.php"; ?>
			<?php include "./views/modals/create-return-shipment.php"; ?>
			<!--/ Modals -->


			<!-- Main Content -->
			<?php include "./views/main.php"; ?>
			<!--/ Main Content -->

	  	</div>

	    <!-- jQuery first, then Bootstrap JS, Moment.Js etc. -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.21.0/moment.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha18/js/tempusdominus-bootstrap-4.min.js"></script>


	    <!-- Add Vue js for manipulation with data, and AXios for Ajax requests -->
		<script src="https://cdn.jsdelivr.net/npm/vue"></script>
		<script src="https://unpkg.com/axios/dist/axios.min.js"></script>


		<!-- Add PDF viewer for displaying Shipping Labels -->
		<script src="js/vendors/pdfobject.min.js"></script>

		<!-- Main JS -->
		<script src="js/app.js"></script>

  	</body> 
</html>