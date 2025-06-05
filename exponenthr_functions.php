<?php
include_once '../.createUsersConfig.php';


/*
 * function getSoapConnector - antiquated way to pass information
 * creates and binds a soap connector
 * returns the handle to the soap connector (think of this as a tcp connection). 
 */
function getSoapConnector() {
	global $cid;//must be a global variable
	global $auth;//must be a global variable
	$opts = array(
		'login'=>'bemis', 
		'username' => 'bemis',
		'password'=>'doesnotmatter', 
		'authentication'=>SOAP_AUTHENTICATION_BASIC,
		'trace' => 1,
		'_use_digest' => false,
	);
	$sc = new SoapClient('https://exponenthr.com/api/Employees.asmx?wsdl', $opts);
	$sclass = new stdClass();
	$sclass->ClientNumber = $cid;
	$sclass->AuthKey = $auth;
	$header_body = $sclass;
	$headers = new SoapHeader("https://exponenthr.com/", 'EHRAuthHeader', $header_body);
	$sc->__setSoapHeaders($headers);
	return $sc;
}

/*
 * function parseOffXMLGarbage 
 * Microsofts XML implementation doesn't play well with PHP without doing a log of extra work
 * this parses off needless style sheets, data type definitions, and gives back simplexml that can be parsed by php
 * in the future do not ever use XML as a transport. it would demonstrate an inability to leap into the future.  
 * return xml string sans rubish xml
 */
function parseOffXMLGarbage($str, $ns_garbageTag) {
		$str = str_replace('>', ">\n", $str);
		$xarr = explode(">\n", $str);
		//pull off the microsoft header garbage from the xml document. utter trash xml...when xml was considered markup. 
		$keep = array();
		$stillTrash = true;
		$i = 0;
		$kc = 0;
		$dropped = 0;
		while($stillTrash && count($xarr)) {
			$row = $xarr[0];//this loop alters the array.  We drop the first line every time in this loop.  if this was $i it skips every other line ;)
			$tk = "<diffgr";
			$pos = strpos($row, $tk);
			if ($pos !== false) {
				$stillTrash = false;  
			}
			$tmp = array_shift($xarr);
			$dropped++;
			$i++;
		}
		$i = count($xarr);
		$stillTrash = true;
		while($stillTrash && $i > 0) {
			$row = $xarr[$i-1];
			$pos = strpos($row, "</diffgr");
			if ($pos !== false) {
				$stillTrash = false;
			}
			$tmp = array_pop($xarr);
			$i--;
			$kc++;
			if ($kc > 10) {
				die();
			}
		}
		for($j=0; $j<count($xarr);$j++) {
			$pos = strpos($xarr[$j], $ns_garbageTag);
			if ($pos !== false) {
				$parts = explode(" ", $xarr[$j]);
				$xarr[$j] = $parts[0].">";
			}
		}
		$str = join(">", $xarr).">";
		return $str;

}
/*
 * function getAllJobInfo - fetch all employee job info objects
 * return array keyed by employeeid for all job info objects
 */
function getAllJobInfo() {
	global $cid;
	$sc = getSoapConnector();
	try {
		$result = $sc->__soapCall('GetJobInfo', array(
			'GetJobInfo' => array(
				'ClientNumber' => '',//$cid,
				'EmployeeID' => ''

			)
		));
		$str = parseOffXMLGarbage($result->GetJobInfoResult->data->any, "<EmployeeInfoJob");
		$xml = simplexml_load_string($str);
		$tmp = array();
		foreach($xml->EmployeeInfoJob as $x) {
			$tmp[(string)$x->EmployeeID] = array(
				'ClientNo' => (string)$x->ClientNo,
				'EmployeeName' => (string)$x->EmployeeName,
				'TK' => (string)$x->TK,
				'Email' => strtolower((string)$x->Email),
				'TheAdd1' => (string)$x->TheAdd1,
				'TheAdd2' => (string)$x->TheAdd2,
				'TheCity' => (string)$x->TheCity,
				'TheState' => (string)$x->TheState,
				'TheZip' => (string)$x->TheZip,
				'TheToll' => (string)$x->TheToll
			);
		}
		return $tmp;

	} catch (Exception $e) {
		print_r($e);
		die("mistakes were made.  go fix them\n");
	}
}

/*
 * function getAllInfo
 * return array keyed by employeeid for all info objects from exponenthr
 */
function getAllInfo() {
	global $cid;
	$sc = getSoapConnector();
	try {
		$result = $sc->__soapCall('GetInfo', array(
			'GetInfo' => array(
				'ClientNumber' => '',//$cid,
				'EmployeeID' => ''

			)
		));
		$str = parseOffXMLGarbage($result->GetInfoResult->data->any, "<EmployeeInfo");
		$xml = simplexml_load_string($str);
		$tmp = array();
		foreach($xml->EmployeeInfoBasic as $x) {
			$tmp[(string)$x->EmployeeID] = array(
				'ClientNo' => (string)$x->ClientNo,
				'EmployeeName' => (string)$x->EmployeeName,
				'TK' => (string)$x->TK,
				'EmployeeID' => (string)$x->EmployeeID,
				'LastName' => (string)$x->LastName,
				'FirstName' => (string)$x->FirstName,
				'MiddleInit' => (string)$x->MiddleInit,
				'NickName' => (string)$x->NickName,
				'ClientName' => (string)$x->ClientName,
				'EStatus' => (string)$x->EStatus,
				'LastHireDate' => (string)$x->LastHireDate,
				'LastTermDate' => (string)$x->LastTermDate,
				'SeniorityDate' => (string)$x->SeniorityDate,
				'JobTitle' => (string)$x->JobTitle,
				'Location' => (string)$x->Location,
				'Division' => (string)$x->Division,
				'Department' => (string)$x->Department,
				'Shift' => (string)$x->Shift,
				'SupervisorNm2' => (string)$x->SupervisorNm2,
				'Supervisor2' => (string)$x->Supervisor2,
				'Supervisor2Email' => (string)$x->Supervisor2Email
			);
		}
		return $tmp;

	} catch (Exception $e) {
		print_r($e);
		die("mistakes were made.  go fix them\n");
	}
}

/*
 * function getAllContactinfo - fetches all employees contact info objects from exponent HR
 * this is mostly a useless function and was used to prototype connecting to the old soap API that exists for ExponentHR
 * return array keyed by employee id
 */
function getAllContactInfo() {
	global $cid;
	$sc = getSoapConnector();
	try {
		$result = $sc->__soapCall('GetContactInfo', array(
			'GetContactInfo' => array(
				'ClientNumber' => '',//$cid,
				'EmployeeID' => ''

			)
		));
		$str = parseOffXMLGarbage($result->GetContactInfoResult->data->any, "<EmployeeInfo");
		$xml = simplexml_load_string($str);
		$tmp = array();
		foreach($xml->EmployeeInfoContact as $x) {
			$tmp[(string)$x->EmployeeID] = array(
				'ClientNo' => (string)$x->ClientNo,
				'EmployeeName' => (string)$x->EmployeeName,
				'TK' => (string)$x->TK
			);
		}
		return $tmp;

	} catch (Exception $e) {
		print_r($e);
		die("mistakes were made.  go fix them\n");
	}
}
