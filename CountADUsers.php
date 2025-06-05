<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
//$jinfo = getJobInfoById();
//$info = getInfoById();
//$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
$setemails = 0;
$nonemails = 0;
$missingr = array();
$delta = array();
foreach($info as $key=>$m) {
	//if their EStatus is not terminated look up their AD record.  
	if ($m['EStatus'] == 'Terminated') {
		continue;
	}
	$iinfo = $jinfo[$key];
	//fetch their AD record
	/*
	 *
	 $iinfo accessed by the following keys
Array
(
    [ClientNo] => 05349
    [EmployeeName] => AARONSON, JESSICA H
    [TK] => 12041
    [Email] => jaaronson@graniterecoverycenters.com
    [TheAdd1] => 6 Manor Parkway
    [TheAdd2] =>
    [TheCity] => Salem
    [TheState] => NH
    [TheZip] =>
    [TheToll] =>
)

	 $m accessed by the following keys
Array
(
    [ClientNo] => 05349
    [EmployeeName] => AARONSON, JESSICA H
    [TK] => 12041
    [EmployeeID] => Y14230
    [LastName] => Aaronson
    [FirstName] => Jessica
    [MiddleInit] => H
    [NickName] =>
    [ClientName] => GRANITE RECOVERY CENTERS, LLC
    [EStatus] => Active
    [LastHireDate] => 2021-12-21T00:00:00-06:00
    [LastTermDate] =>
    [SeniorityDate] => 2021-11-08T00:00:00-06:00
    [JobTitle] => Admissions Coordinator - Residential
    [Location] => NH - Salem Manor Corp
    [Division] => Granite
    [Department] => Administration
    [Shift] => Unassigned Program
    [SupervisorNm2] => Mahan, Michael P
    [Supervisor2] => V13939
    [Supervisor2Email] => mmahan@graniterecoverycenters.com
)


 */	
	//fetch mgr active directory ($mad)
	//fetch usr active directory ($uad);
	
	if (!isset($au[$iinfo['Email']])) {
		
		$nonemails++;
	} else {
		$setemails++;
		$ad_title = $au[$iinfo['Email']]['title'][0];
		$eh_title = $m['JobTitle'];
		if ($ad_title != $eh_title) {
			
			$delta[$iinfo['Email']][] = array(
				'type' => 'title_change', 
				'activedirectory_title'=>$ad_title, 
				'exponenthr_title'=>$eh_title);
			//ad department maps to ehr Department
			//EHR Field Name	AD Field Name
			//JobTitle		title
			//Location		physicaldeliveryofficename
			//Division		company	
			//Department		department
			///manager		Supervisor2Email

		}
		$ad_manager = $au[$iinfo['Email']]['manager'][0];
		$eh_manager = $au[$m['Supervisor2Email']]['distinguishedname'][0];
		if ($ad_manager != $eh_manager) {
			$delta[$iinfo['Email']][] = array(
				'activedirectory_manager' => $ad_manager,
				'exponenthr_manager' => $eh_manager
			);
		}

	}
	//die("first item in ExponentHR\n");

}
print_r($delta);
//generate delta report for the following fields:
//title
//
?>
