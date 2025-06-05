<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';

$au = getTerminatedUsers();
$lconn = ad_bind();
$jinfo = getAllJobInfo();
$info = getAllInfo();
//Setup Counters and storage arrays
$setemails = 0;
$nonemails = 0;
$missingr = array();
$delta = array();

//loop over all employees from ExponentHR
foreach($info as $key=>$m) {

	//if their EStatus is not terminated look up their AD record.  
	if ($m['EStatus'] != 'Terminated') {
		continue;
	}

	$iinfo = $jinfo[$key];
	if (!isset($au[$iinfo['Email']])) {
		$nonemails++;
	} else {
		/* 
		 * SECTION Supervisor2Email (manager)
		 */
		$ad_manager = '';
		if (isset($au[$iinfo['Email']])) {
			$ai = $au[$iinfo['Email']];
			if (isset($ai['manager'])) {
				$ad_manager = $au[$iinfo['Email']]['manager'][0];
			}
		}
		if ($ad_manager == '') {
			//continue since it's already blank and we do not need to update
			continue;
		}
		$eh_manager = '';
		$delta[$iinfo['Email']][] = array(
			'type' => 'manager_change',
			'activedirectory_manager' => $ad_manager,
			'exponenthr_manager' => ''
		);
	}
}
//die("first item in ExponentHR\n");
$discrepencies = 0;
foreach($delta as $k=>$d) {
	foreach($d as $job) {
		$discrepencies++;
		$job_t = $job['type'];
		$job['email'] = $k;
		$job_t($au, $job, $lconn);
	}
}
echo "number of changes found: $discrepencies\n";
//generate delta report for the following fields:
//title
//
?>
