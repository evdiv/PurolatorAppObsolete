<div class="container-fluid">
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container">

			<a class="navbar-brand" href="/" target="_blank">YOUR LOGO</a> 

			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			    <span class="navbar-toggler-icon"></span></button>

			  <div class="collapse navbar-collapse" id="navbarSupportedContent">
			    <ul class="navbar-nav mr-auto">
				    <li class="nav-item active">
				        <a class="nav-link" href="https://ship.purolator.com/" target="_blank">
							<img src="images/logo-purolator-sm.png" height="24px">
				        </a> 
				    </li>

				    <!-- <li class="nav-item dropdown">
				        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Help</a>

				        <div class="dropdown-menu" aria-labelledby="navbarDropdown">


				        	<a class="dropdown-item" 
				        		href="https://www.canadapost.ca/cpo/mc/personal/postalcode/fpc.jsf?LOCALE=en"
				        		target="_blank">Find a Postal Code</a>
				        </div>
				    </li> -->
			    </ul>

				<div class="my-2 my-lg-0">
					<button type="button" class="btn btn-warning btn-sm" 
					v-on:click="location.href='/purolator/'">Create New</button>
					

					<button type="button" class="btn btn-info btn-sm" 
						v-on:click="displayRates" 
						data-toggle="modal" data-target="#ratesModal">Get Rates</button>


					<button type="button" class="btn btn-info btn-sm" 
						data-toggle="modal" 
						data-target="#createShipmentModal"  
						v-on:click="resetShipmentDetails">Create Shipment</button>


					<button type="button" class="btn btn-info btn-sm" 
								data-toggle="modal" 
								data-target="#createReturnShipmentModal" 
								v-on:click="resetShipmentDetails" 
				          		href="#">Create Return Shipment</button>		


					<button type="button" class="btn btn-secondary btn-sm" 
								data-toggle="modal" 
								data-target="#manifestModal"  
								v-on:click="getManifest"
				          		href="#"> End of the Day</button>	

				</div>

	  		</div>
		</div>
	</nav>
</div>