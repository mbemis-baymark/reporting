<?php
//include ExponentHR webservice function calls
include_once 'exponenthr_functions.php';

//include ActiveDirectory 
include_once 'ad_functions.php';
$global_kill_limit = 20000;
$global_kill_count = 0;
//FETCH All Employee Information Objects from ExponentHR
$jinfo = getAllJobInfo();
$info = getAllInfo();

//FETCH ALL ActiveDirectory users
//Get ActiveDirectory Connector
$lconn = ad_bind();
$au = fetchAllAdUsers($lconn);

//Setup Counters and storage arrays
$setemails = 0;
$nonemails = 0;
$missingr = array();
$delta = array();

//loop over all employees from ExponentHR
echo "employee_email,job_title,location,division,supervisor_email\n";
foreach($info as $key=>$m) {

	//if their EStatus is not terminated look up their AD record.  
	if ($m['EStatus'] == 'Terminated') {
		continue;
	}

	$iinfo = $jinfo[$key];
	//if their primary email is not found in AD add it to the counter
	if (!isset($au[$iinfo['Email']])) {
		$nonemails++;
	} else {
		/* MAIN DIFFERENCE CALCULATION ENGINE */
		//MAPPING TABLES 	
		//EHR Field Name	AD Field Name
		//JobTitle		title
		//Location		physicaldeliveryofficename
		//Division		company	
		//Department		department
		///manager		Supervisor2Email
		//EmployeeID		extensionattribute14
		echo $iinfo['Email'].",'".$m['JobTitle']."','".$m['Location']."','".$m['Division']."',".$m['Supervisor2Email']."\n";
	}
}
die();
?>
