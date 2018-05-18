<?php

namespace Purolator;

class Locations { 

	private $db;


	public function __construct() {

		$this->db = new Database();
	}


    public function GetProvinceCodeByAccountsID($AccountID) {
        $result = $this->db->query("SELECT p.ProvinceCode
                                		FROM Provinces p, Accounts a
                                		WHERE p.ProvincesID = a.ProvincesID
                                		AND a.AccountsID = " . $AccountID . "
                                		LIMIT 1");
        $row = $result->fetch_assoc();
        
        return $row['ProvinceCode'];
    }

} // class
?>