<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
$report_file = '/opt/report_csv/HR/missing_supervisor_in_ad.csv';
$jinfo = getAllJobInfo();
$info = getAllInfo();
$lconn = ad_bind();
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
	
	if (!isset($au[$iinfo['Email']])) {
		
		$nonemails++;
	} else {
		$setemails++;
		$ad_title = '';
		if (isset($au[$iinfo['Email']]) && isset($au[$iinfo['Email']]['title'])) {
			$ad_title = $au[$iinfo['Email']]['title'][0];
		}
		$eh_title = $m['JobTitle'];
		if ($ad_title != $eh_title) {
			$delta[$iinfo['Email']][] = array('activedirectory_title'=>$ad_title, 'exponenthr_title'=>$eh_title);
			//ad department maps to ehr Department
			//EHR Field Name	AD Field Name
			//JobTitle		title
			//Location		physicaldeliveryofficename
			//Division		company	
			//Department		department
			///manager		Supervisor2Email

		}
		$ad_manager = '';
		if (isset($au[$iinfo['Email']]) && isset($au[$iinfo['Email']]['manager'])) {
			$ad_manager = $au[$iinfo['Email']]['manager'][0];
		}
		$eh_manager = '';
		if (isset($au[$m['Supervisor2Email']]) && isset($au[$m['Supervisor2Email']]['distinguishedname'])) {
			$eh_manager = $au[$m['Supervisor2Email']]['distinguishedname'][0];
		}
		if ($ad_manager != $eh_manager) {
			$delta[$iinfo['Email']][] = array(
				'activedirectory_manager' => $ad_manager,
				'exponenthr_manager' => $eh_manager
			);
		}
		if ($eh_manager == '') {
			$missingr[$iinfo['Email']]['missing_manager_in_ad'] = $m['Supervisor2Email'];
		}

	}
	//die("first item in ExponentHR\n");

}
file_put_contents($report_file, "exponent_hr_email,supervisor_email\n");
foreach($missingr as $key=>$value) {
	$str = $key.",".$value['missing_manager_in_ad']."\n";
	file_put_contents($report_file, $str, FILE_APPEND);
}
?>
