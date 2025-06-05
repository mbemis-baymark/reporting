<?php
//include ExponentHR webservice function calls
include_once 'exponenthr_functions.php';
include_once 'email_functions.php';
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

//set AD Status (Terminated|Active)
foreach($au as $key=>$m) {
	$pos = strpos($m['distinguishedname'][0], 'Terminated');
	if ($pos !== false) {
		$au[$key]['status'] = 'Terminated';
	} else {
		$au[$key]['status'] = 'Active';
	}
}
$missing_in_ad = array();
//loop over all employees from ExponentHR
$email_str = '';
$email_dt = "Terms found for ".date("Y-m-d H:i:s");
$term_ct = 0;
$old_terms = array();
$modern_terms = array();
$dnow = time();

$hours_back = 96;
$start = $dnow - $hours_back*60*60;
foreach($info as $key=>$m) {
	$ij = $jinfo[$key];
	$email = $ij['Email'];
	if ($email == '') {
		continue;
	}
	//if their EStatus is terminated look up their AD record.  
	if ($m['EStatus'] == 'Terminated') {
		//only look at the last 24 hours
		$dt = strtotime($m['LastTermDate']);
		if ($dt >= $start && $dt <= $dnow) {
			//only look at the last
			//check to see if their termed date time is past now
			if (isset($au[$email])) {
				echo "$key {$m['EStatus']} in ad $email is set to ".$au[$email]['status']."\n";
				//if past now do the following
				//add them to a list to audit
				//reset their password
				//move to the terminations group
				//email the TrayTerminations@baymark.com email address a list of termed emails.  
				$term_ct++;
				$modern_terms[]  = "Term date:{$m['LastTermDate']} for {$m['LastName']}, {$m['FirstName']} $key";
			} else {
				//state these emails are missing. 
				$missing_in_ad[] = $email;
			}
		} else {
			//skipping as it wasn't in the last 24 hours. 
			//echo "SKIPPING $key {$m['EStatus']} in ad $email is set to ".$au[$email]['status']."\n";

			$old_terms[]  = "Term date:{$m['LastTermDate']} for $key";
			continue;
		}
	}
	else {
		continue;
	}

}
rsort($old_terms);
rsort($modern_terms);
$email_str = join ('</br>', $old_terms);
$just_ran = join('</br>', $modern_terms);
$email_str = "Number of terms found: $term_ct\n\n</br>Terms That just Ran</br>$just_ran</br></br></br>";
$to = array('mbemis@baymark.com', 'trayterminations@baymark.com');

send_email($to, $email_dt, $email_str);
?>
