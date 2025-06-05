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



		/*
		 * Each SECTION below is for calculating the difference of one Field from
		 * ExponentHR to AD and adding a job to the job engine
		 */


		/*
		 * SECTION JobTitle
		 */
		$setemails++;
		$ad_title = '';
		if (isset($au[$iinfo['Email']])) {
			if (isset($au[$iinfo['Email']]['title'])) {
				$ad_title = $au[$iinfo['Email']]['title'][0];
			}
		}
		$eh_title = $m['JobTitle'];
		if ($ad_title != $eh_title) {

			$delta[$iinfo['Email']][] = array(
				'type' => 'title_change', 
				'activedirectory_title'=>$ad_title, 
				'exponenthr_title'=>$eh_title);

		}

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
		$eh_manager = '';
		if (isset($au[$m['Supervisor2Email']])) {
			$eh_manager = $au[$m['Supervisor2Email']]['distinguishedname'][0];
		}
		if ($ad_manager != $eh_manager) {
			$delta[$iinfo['Email']][] = array(
				'type' => 'manager_change',
				'activedirectory_manager' => $ad_manager,
				'exponenthr_manager' => $eh_manager
			);
		}

		/*
		 * SECTION EmployeeID
		 */
		$ad_employeeid = '';
		if (isset($au[$iinfo['Email']])) {
			$ai = $au[$iinfo['Email']];
			if (isset($ai['extensionattribute14'])) {
				$ad_employeeid = $au[$iinfo['Email']]['extensionattribute14'][0];
			}
		}
		$eh_employeeid = $m['EmployeeID'];

		if ($ad_employeeid != $eh_employeeid) {
			$delta[$iinfo['Email']][] = array(
				'type' => 'employeeid_change',
				'activedirectory_employeeid' => $ad_employeeid,
				'exponenthr_employeeid' => $eh_employeeid
			);
		}

		/*
		 * SECTION Location
		 */
		//Location		physicaldeliveryofficename
		$ad_location = '';
		if (isset($au[$iinfo['Email']])) {
			$ai = $au[$iinfo['Email']];
			if (isset($ai['physicaldeliveryofficename'])) {
				$ad_location = $au[$iinfo['Email']]['physicaldeliveryofficename'][0];
			}
		}
		$eh_location = $m['Location'];

		if ($ad_location != $eh_location) {
			$delta[$iinfo['Email']][] = array(
				'type' => 'location_change',
				'activedirectory_location' => $ad_location,
				'exponenthr_location' => $eh_location
			);
		}

		/*
		 * SECTION Department
		 */
		//Department		department
		$ad_department = '';
		if (isset($au[$iinfo['Email']])) {
			$ai = $au[$iinfo['Email']];
			if (isset($ai['department'])) {
				$ad_department = $au[$iinfo['Email']]['department'][0];
			}
		}
		$eh_department = $m['Department'];

		if ($ad_department != $eh_department) {
			$delta[$iinfo['Email']][] = array(
				'type' => 'department_change',
				'activedirectory_department' => $ad_department,
				'exponenthr_department' => $eh_department
			);
		}

		/*
		 * SECTION Division
		 */
		//Division		company	
		$ad_division = '';
		if (isset($au[$iinfo['Email']])) {
			$ai = $au[$iinfo['Email']];
			if (isset($ai['company'])) {
				$ad_division = $au[$iinfo['Email']]['company'][0];
			}
		}
		$eh_division = $m['Division'];

		if ($ad_division != $eh_division) {
			$delta[$iinfo['Email']][] = array(
				'type' => 'division_change',
				'activedirectory_division' => $ad_division,
				'exponenthr_division' => $eh_division
			);
		}
	}
}

//Build a delta.csv file for inspection and QA. 
/*
 * file_put_contents('delta.csv', "Email, Automation Job Type, ExponentHR Value, ActiveDirectory Value\n");
$str = '';
foreach($delta as $k=>$d) {
	foreach($d as $job) {
		$tarr = array_values($job);
		$job_t = $job['type'];
		$job['email'] = $k;
		$tarr[2] = str_replace(',', ' ', $tarr[2]);
		$tarr[1] = str_replace(',', ' ', $tarr[1]);
		$str .= "$k, $job_t, {$tarr[2]}, {$tarr[1]}\n";
	}
}
file_put_contents('delta.csv', $str, FILE_APPEND);
 */

//execute each job type from the calculation array
$discrepencies = 0;
//for each delta split it out as the key (k) to actual singular delta (d) and execute the job

foreach($delta as $k=>$d) {
	//each key may have 1 or more jobs
	foreach($d as $job) {
		//increment discrepency count for use in reporting at the end of the file
		$discrepencies++;

		//pull out the job type (this is also a function name from the ad_functions.php file
		$job_t = $job['type'];

		//pull out the email associated with the job type
		//this is used to look up the UPN from the AD tree
		$job['email'] = $k;

		echo "\n\nat  job $job_t for user $k\n";
		print_r($job);
		//dynamically call the job function which is set from the difference calculation engine above. 
usleep(5000);
		$job_t($au, $job, $lconn);

		//put in limiters to test and just run one job at a type. 
		echo "global_kill_count reached gkc=$global_kill_count, gkl=$global_kill_limit\n";
		$global_kill_count++;
		if ($global_kill_count > $global_kill_limit) {
			die("global_kill_count reached gkc=$global_kill_count, gkl=$global_kill_limit\n");

		}
		echo "global_kill_count stats  gkc=$global_kill_count, gkl=$global_kill_limit\n";
		
	}
}
echo "number of changes found: $discrepencies\n";
//print_r($delta);
//generate delta report for the following fields:
//title
//
?>
