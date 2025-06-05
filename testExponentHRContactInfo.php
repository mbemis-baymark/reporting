<?php

include_once '../.createUsersConfig.php';
include_once 'exponenthr_functions.php';
getExponentHRContacts($cid, $auth, $ep);
$opts = array(
	'login'=>'bemis', 
	'username' => 'bemis',
	'password'=>'doesnotmatter', 
	'authentication'=>SOAP_AUTHENTICATION_BASIC,
	'trace' => 1,
	'_use_digest' => false,
);
$sc = new SoapClient('https://exponenthr.com/api/Employees.asmx?wsdl', $opts);
print_r($sc);
print_r($sc->__getFunctions());
$sclass = new stdClass();
$sclass->ClientNumber = $cid;
$sclass->AuthKey = $auth;
$header_body = $sclass;
$headers = new SoapHeader("https://exponenthr.com/", 'EHRAuthHeader', $header_body);
$sc->__setSoapHeaders($headers);
try {
$result = $sc->__soapCall('GetContactInfo', array(
	'GetContactInfo' => array(
		'ClientNumber' => $cid,
		'EmployeeID' => ''

	)
));
} catch (Exception $e) {
	
	print_r($e);
}
print_r($sc->__getLastRequest());
print_r($sc->__getLastResponse());
?>
