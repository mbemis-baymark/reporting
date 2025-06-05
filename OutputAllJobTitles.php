<?php
include_once 'exponenthr_functions.php';
$jinfo = getAllInfo();
$titles = array();
foreach($jinfo as $e) {
	$titles[$e['JobTitle']] = $e['JobTitle'];
}
sort($titles);
foreach($titles as $i=>$t) {
	echo $i.",\"".$t."\"\n";
}
?>
