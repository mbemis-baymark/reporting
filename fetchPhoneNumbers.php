<?php
include_once 'ad_functions.php';
//include the username (u), password (p), 
//include '../.createUsersConfig.php';
//$ldapuri = "ldap://$h:389";
//$lconn = ldap_connect($ldapuri);
//ldap_set_option($lconn, LDAP_OPT_PROTOCOL_VERSION, 3);
$all_users = array();
$all_emails = array();
//$lbind = ldap_bind($lconn, $u, $p);
//if ($lbind) {
//	echo "bind succeeded\n";
$lconn = ad_bind();
$ldap_base_dn = 'OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
$start = ord('a');
for($i=0; $i<26; $i++) {
	$search_filter = 'displayName='.chr($start + $i).'*';
	$r = ldap_search($lconn, $ldap_base_dn, $search_filter);
	$lres = ldap_get_entries($lconn, $r);

	print_r($lres);
	$count = $lres['count'];
	for($j=0; $j < $count; $j++) {
		$o = $lres[$j];
		$tmp = array();
		if (isset($o['manager'])) {
			$tmp['manager'] = $o['manager'][0];
		}
		if (isset($o['telephonenumber'])) {
			$tmp['telephonenumber'] = $o['telephonenumber'][0];
		} else {
			$tmp['telephonenumber'] = 'not set';//$o['telephonenumber'][0];
		}

		if (isset($o['displayname'])) {
			$tmp['displayname'] = $o['displayname'];
		}
		if (isset($o['proxyaddresses'])) {
			$tmp['proxyaddresses'] = $o['proxyaddresses'];
			foreach($o['proxyaddresses'] as $e) {
				$e = strtolower($e);
				$pos = strpos($e, 'smtp');
				if ($pos !== false) {
					$parts = explode(':', $e);
					$all_emails[] = count($parts) > 1 ? $parts[1] : $parts[0];
				}
			}
		}
		$all_users[] = $tmp;
	}
}
file_put_contents('phones.csv', 'email, phone, manager'."\n");
print_r($all_users);
die('NO');
foreach($all_users as $u) {
	if (isset($u['proxyaddresses'])) {
		$uh = '';
		foreach($u['proxyaddresses'] as $e) {
			$parts = explode(':', $e);
			//echo "e = $e\n";
			//print_r($parts);
			if (!is_array($parts)) {
				continue;//this is not an array and I dont care for it. the key is count...so it's a stupid number 
			}
			//print_r($parts);
			$uh = count($parts) > 1 ? $parts[1] : $parts[0];
			if (strlen($uh) <= 2) {
				continue;
			}
			$pos = strpos($uh, ';');
			if ($pos!==false) {
				continue;
			}
			$pos = strpos($uh, 'onmicrosoft');
			if ($pos!==false) {
				continue;
			}
			$pos = strpos($uh, 'Exchange');
			if ($pos!==false) {
				continue;
			}
			$pos = strpos($uh, 'cn=Recipients');
			if ($pos !==false) {
				continue;
			}
			$uh = str_pad($uh, 50, ' ');
			file_put_contents('phones.csv', $uh.",".$u['telephonenumber']."\n", FILE_APPEND);	
		}
	}
}
?>
