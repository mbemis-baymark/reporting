<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
$jinfo = getAllJobInfo();
$EHR_emails = array();
foreach($jinfo as $k=>$v) {
	if (trim($v['Email'])) {		
		$EHR_emails[$v['Email']] = 1;
	}
}
//$info = getInfoById();
$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
//print_r($au);
$AD_emails = array();
foreach($au as $k=>$v) {
	if (isset($v['userprincipalname'])) {
		$AD_emails[$v['userprincipalname'][0]] = 1;
	}
	if (isset($v['proxyaddresses'])) {
		foreach($v['proxyaddresses'] as $kk=>$vv) {
			$vv = strtolower($vv);
			$pos = strpos($vv, 'smtp:');
			if ($pos !== false) {
				$parts = explode('smtp:', $vv);
				//print_r($parts);
				$AD_emails[$parts[0]] = 1;
			} else {
				//echo "smtp not found $vv\n";
			}
		}
	}
}
//print_r($AD_emails);
$inadnotehr = array();
foreach($AD_emails as $e) {
	if (!isset($EHR)) {
	}
}
?>
