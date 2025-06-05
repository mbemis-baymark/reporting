<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
//$jinfo = getJobInfoById();
//$info = getInfoById();
$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
//print_r($au);
$uarr = array();
$f = file_get_contents('/tmp/names.csv');
echo $f."\n";
$lines = explode("\n", $f);
$emails = array();
foreach($lines as $l) {
	$parts = explode(',', $l);
	$l = $parts[0];
	$email = trim($l);
	$emails[strtolower($email)] = 1;

}
$csv = '';
foreach($au as $email=>$u) {
	$tmp = array();
	if (!isset($emails[$email])) {
		echo "continuing $email\n";
		continue;
	} else {
		echo "$email found and outputting the data to a csv file\n";
	}
	$tmp[] = $email;
	$tmp[] = $u['cn'][0];
	if (!isset($u['givenname'])) {
		$tmp[] = 'no givenname';
	} else {
		$tmp[] = $u['givenname'][0];
	}
	$tmp[] = isset($u['sn']) ? $u['sn'][0] : 'no surname';

	if (!isset($u['manager'])) {
		$tmp[] = 'not set (termed?)';
	} else {
		$tmp[] = $u['manager'][0];
	}	
	$str = join(',', $tmp);
	$uarr[] = $str;
	echo $str."\n";
}
file_put_contents('quarto.csv', join("\n", $uarr));
?>
