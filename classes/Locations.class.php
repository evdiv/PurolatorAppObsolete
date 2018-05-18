<?php

namespace Purolator;

class Locations { // class

    function GetProvinceCodeByAccountsID($AccountID) {
        $result = mysql_query("SELECT p.ProvinceCode
                                FROM Provinces p, Accounts a
                                WHERE p.ProvincesID = a.ProvincesID
                                AND a.AccountsID = " . $AccountID . "
                                LIMIT 1");
        $row = mysql_fetch_assoc($result);
        return $row['ProvinceCode'];
    }

} // class
?>