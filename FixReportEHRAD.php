<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
$report_file = '/opt/report_csv/HR/missing_email_linkage_exponenthr_activedirectory.csv';

//get employee JobInfo from ExponentHR for ALL users keyed by email address
$jinfo = getAllJobInfo();

//get employee info from ExponentHR for ALL users keyed by Email address
$info = getAllInfo();
$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
$setemails = 0;
$nonemails = 0;
$missingr = array();
foreach($info as $key=>$m) {
	//if their EStatus is not terminated look up their AD record.  
	if ($m['EStatus'] == 'Terminated') {
		continue;
	}
	$iinfo = $jinfo[$key];
	if (!isset($au[$iinfo['Email']])) {
		$pos = strpos(strtolower($iinfo['Email']), 'noemail');
		if ($pos !== false) {
			echo "found noemail for ".$iinfo['Email']." so skipping\n";
			continue;
		}	
		$tmpr = array_merge($iinfo, $m);	
		$missingr[$iinfo['Email']] = $tmpr;
		$nonemails++;
	} else {
		$setemails++;
	}
	//die("first item in ExponentHR\n");

}
//generate missing report
foreach($missingr as $key=> $value) {
	$keys = array($value['LastName'], $value['FirstName']);
	//echo "creating fix suggestion for $key\n";
	$matches = getMatchingNodes('cn', $keys, $au);	
	$missingr[$key]['matches'] = $matches;
}
file_put_contents($report_file, 'EmployeeID,FirstName,LastName,ExponentEmailAddress,AddressFoundInActiveDirectory,NumberOfActiveDirectoryMatches,ExponentEmailDomain'."\n");
foreach($missingr as $key=>$value) {
	$str = $key.",";
	$number = is_array($value['matches']) ? count($value['matches']) : 0;
	$admail = "first and last not found in ActiveDirectory - needs HR to create a ticket to have IT make the user";
	$upn = findUPNByAnyEmailAddress($au, trim($value['Email']));
	if ($upn !== false) {
		$admail = "upn found in alias $upn";
	}
	if ($number) {
		if (isset($value['matches'][0]['userprincipalname'])) {
		$admail = strtolower($value['matches'][0]['userprincipalname'][0]);
		} else {
			//echo "mail not set!\n";
			$admail = "Exists in AD - does not have an email address.";
		}
	}
	$edomain = explode('@', $value['Email']);
	$edomain = isset($edomain[1]) ? $edomain[1] : '';
	$str = $value['EmployeeID'].",".$value['FirstName'].",".$value['LastName'].",".$str.$admail.",".$number.",$edomain\n";
	echo $str."\n";
	file_put_contents($report_file, $str, FILE_APPEND);
}
echo "Number of emails found in ad with email set is: $setemails\n";
echo "Number of missing emails in ad with email not set is: $nonemails\n";
$groups = array();
foreach($au as $a) {
	if (isset($a['memberof'])) {
		foreach($a['memberof'] as $m) {
			$groups[$m] = 1;
		}
	}
}
$gcount = count($groups);
echo "Number of groups with users in them is: $gcount\n";
?>
