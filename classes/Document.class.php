<?php 

namespace Purolator;

class Document {

    private $db;

    private $incomingData;
    private $client;
    private $request;
    public $response;  

    public $errors = array();
    public $pdfUrl = '';   
    public $manifestType = '';
    public $manifestDescription = '';
    public $manifestStatus = '';



    public function __construct($incomingData = '') {

        $this->db = new Database();

        $this->incomingData = $incomingData;
        $this->client = $this->createPWSSOAPClient();

        $this->request = new \stdClass();
        $this->response = new \stdClass();
    }


    public function getLabel() {

        $this->executeLabelRequest();
        $this->getErrors();
        $this->getPdfUrl();
    }


    public function getManifest() {

        $this->executeManifestRequest();
        $this->getErrors();
        $this->getManifestDetails();
    }




	private function createPWSSOAPClient() {

  		$client = new \SoapClient( "./wsdl/ShippingDocumentsService.wsdl", 
			array	(
                'trace'		=>	true,
                'location'	=>	APP_PUROLATOR_SHIPMENT_DOCUMENTS_URL,
                'uri'		=>	"http://purolator.com/pws/datatypes/v1", 
                'login'		=>	APP_PUROLATOR_KEY,
                'password'	=>	APP_PUROLATOR_PASS
            )
        );

	  	$headers[] = new \SoapHeader ( 'http://purolator.com/pws/datatypes/v1',
	        'RequestContext', 
                array (
                        'Version'           =>  '1.3',
                        'Language'          =>  'en',
                        'GroupID'           =>  'xxx',
                        'RequestReference'  =>  'Example Code' 
                    )
            ); 


  		$client->__setSoapHeaders($headers);
  		
  		return $client;
	}



    private function executeLabelRequest() {

        $this->request->DocumentCriterium->DocumentCriteria->PIN->Value = $this->incomingData['pin'];
        $this->request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = $this->incomingData['documentType'];

        try {
            $this->response = $this->client->GetDocuments($this->request);

        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }
    }



    private function executeManifestRequest() {

        $this->request->ShipmentManifestDocumentCriterium->ShipmentManifestDocumentCriteria->ManifestDate = $this->incomingData['manifestDate'];

        try {
            $this->response = $this->client->GetShipmentManifestDocument($this->request);
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



    private function getPdfUrl() {

        $responseArray = json_decode(json_encode($this->response), true);

        if(empty($responseArray)) {
            return;
        }

        try {
            $this->pdfUrl = $this->response->Documents->Document->DocumentDetails->DocumentDetail->URL;
        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }
    }


    private function getManifestDetails() {

        $responseArray = json_decode(json_encode($this->response), true);

        if(empty($responseArray)) {
            return;
        }

        try {
            $this->pdfUrl = $this->response->ManifestBatches->ManifestBatch->ManifestBatchDetails->ManifestBatchDetail->URL;
            $this->manifestType = $this->response->ManifestBatches->ManifestBatch->ManifestBatchDetails->ManifestBatchDetail->DocumentType;            
            $this->manifestDescription = $this->response->ManifestBatches->ManifestBatch->ManifestBatchDetails->ManifestBatchDetail->Description;
            $this->manifestStatus = $this->response->ManifestBatches->ManifestBatch->ManifestBatchDetails->ManifestBatchDetail->DocumentStatus;            
        } catch(Exception $e) {

            $this->errors[] = "Exception:" . $e;
        }

    }
}