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
$marr = array();//main array to hold all locations
$header = '';
$keys = array();
foreach($info as $key=>$m) {
	$j = $jinfo[$key];
	$tarr = [];
	if ($m['EStatus'] == 'Terminated') {
		continue;
	}
	//$tarr['clinic_name'] = str_replace(',', ' ', $m['ClientName']);
	//$tarr['location'] = str_replace(',', ' ', $m['Location']);
	//need business type (OTP, OBOT, RTC, Special Care, Coleman, Mahajan). 
	//TCD Name
	//CELL Number
	//RDO is needed
	//RVP
	//Main Number to the clinic
	//RDO gives us the cell number - NEEDS TO BE IN EXPONENT HR
	//$tarr['division'] = str_replace(',', ' ', $m['Division']);
	//$tarr['department'] = str_replace(',', ' ', $m['Department']);
	/*
	 *Array
(
    [ClientNo] => 05308
    [EmployeeName] => AARON, DONALD
    [TK] => 10591
    [Email] => daaron@hcrcenters.com
    [TheAdd1] => 9 Forbes Road
    [TheAdd2] =>
    [TheCity] => Woburn
    [TheState] => MA
    [TheZip] => 781/838-6757
    [TheToll] =>
)
	 */
	$tarr['add1'] = str_replace(',', ' ', $j['TheAdd1']);
	$tarr['add1'] = str_replace(',', ' ', $j['TheAdd1']);
	$tarr['add2'] = str_replace(',', ' ', $j['TheAdd2']);
	$tarr['city'] = str_replace(',', ' ', $j['TheCity']);
	$tarr['state'] = str_replace(',', ' ', $j['TheState']);
	$tarr['zip'] = str_replace(',', ' ', $j['TheZip']);
	$keys = array_keys($tarr);
	$main_key = '';
	foreach($keys as $tmpkey) {
		$main_key .= $tarr[$tmpkey];
	}
	$marr[$main_key] = $tarr;
	print_r($tarr);
	$header = join(',', $keys);
}
file_put_contents("locations.csv", $header."\n");
$lines = '';
print_r($marr);
sort($marr);
foreach($marr as $m) {
	$lines .= join(', ', array_values($m))."\n";
}
file_put_contents("locations.csv", $lines, FILE_APPEND);
?>
