<?php
include_once '../.createUsersConfig.php';

// Polyfill for paged results functions when the LDAP extension lacks them.
if (!function_exists('ldap_control_paged_result')) {
    /**
     * Emulates ldap_control_paged_result using ldap_set_option.
     */
    function ldap_control_paged_result($link, $pageSize, $isCritical = false, $cookie = '')
    {
        $ctrl = [
            [
                'oid' => LDAP_CONTROL_PAGEDRESULTS,
                'iscritical' => $isCritical,
                'value' => [
                    'size'   => $pageSize,
                    'cookie' => $cookie,
                ],
            ],
        ];

        return ldap_set_option($link, LDAP_OPT_SERVER_CONTROLS, $ctrl);
    }

    /**
     * Retrieves the cookie from a paged search response.
     */
    function ldap_control_paged_result_response($link, $result, &$cookie)
    {
        $controls = [];
        ldap_parse_result($link, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);

        if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
            $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
        } else {
            $cookie = '';
        }

        return true;
    }
}
function ad_bind() {
	global $h;
	global $p;
	global $u;
	$ldapuri = "ldap://$h:389";
	$lconn = ldap_connect($ldapuri);
	ldap_set_option($lconn, LDAP_OPT_PROTOCOL_VERSION, 3);
	if ($lconn) {
		$lbind = ldap_bind($lconn, $u, $p);
		if ($lbind) {
			return $lconn;
		}
	}
	return false;
}
function fetchADUser($email, $lconn) {
        $eparts = preg_split('/[@,\.]/', $email);
        $ldap_base_dn = 'OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
        $filter = "cn=".$eparts[0].'*';
        $sr = ldap_search($lconn, $ldap_base_dn, $filter);
        $lres = ldap_get_entries($lconn, $sr);
        //make object to return
        $tmp = $lres;
        return $tmp;
}

function ldap_paged_search($lconn, $base_dn, $filter, $attributes = array(), $pageSize = 1000) {
        $cookie = '';
        $result = array('count' => 0);
        do {
                ldap_control_paged_result($lconn, $pageSize, true, $cookie);
                $search = ldap_search($lconn, $base_dn, $filter, $attributes);
                if ($search === false) {
                        break;
                }
                $entries = ldap_get_entries($lconn, $search);
                for ($i = 0; $i < $entries['count']; $i++) {
                        $result[$result['count']] = $entries[$i];
                        $result['count']++;
                }
                ldap_control_paged_result_response($lconn, $search, $cookie);
        } while ($cookie !== null && $cookie != '');
        return $result;
}
function fetchAllAdGroups($lconn) {
	$result = ldap_read($lconn, '', '(objectClass=*)', ['supportedControl']);
	$res = ldap_get_entries($lconn, $result)[0]['supportedcontrol'];
	if (!in_array(LDAP_CONTROL_PAGEDRESULTS, $res)) {
		die("This server does not support paged result control");
	}
	$all_groups = array();
	$lconn = ad_bind();
	$pageSize = 512;
	$cookie = '';
	do {
		//active users base dn
		//$ldap_base_dn = 'OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
		//$start = ord('a');
		// Set search base DN (Distinguished Name) and filter
		//ldap_control_paged_result($lconn, $pageSize, true, $cookie);
		$base_dn = "DC=ad,DC=baymarkhealth,DC=com"; // Replace with your own base DN
		$filter = "(&(objectCategory=group))";

		// Search for all groups in the Active Directory
		$res = ldap_search(
			$lconn, 
			$base_dn, 
			$filter,
			['cn'],
			0,
			0,
			0,
			LDAP_DEREF_NEVER,
			[[
				'oid' => LDAP_CONTROL_PAGEDRESULTS, 
				'value' => [
					'size' => $pageSize, 
					'cookie'=>$cookie
				]
			]]
		);
		print_r(ldap_error($lconn));
		echo "\n";

		ldap_get_option($lconn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
		echo "error: $err\n";
		die();
		print_r($res);
		// Get entries from the search results
		ldap_parse_result($lconn, $res, $errcode , $matcheddn , $errmsg , $referrals, $controls);
		$entries = ldap_get_entries($lconn, $search_results);
		// Update the cookie for the next iteration
		foreach ($entries as $entry) {
			echo "cn: ".$entry['cn'][0]."\n";
		}
		if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
			// You need to pass the cookie from the last call to the next one
			$cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
		} else {
			$cookie = '';
		}
		$all_groups[] = $entries;
	} while (!empty($cookie));
	return $all_groups;

}
function fetchAllAdUsers($lconn) {
	$all_users = array();
	$all_emails = array();
	$lconn = ad_bind();
	//active users base dn
	$ldap_base_dn = 'OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
	$start = ord('a');
        for($i=0; $i<26; $i++) {
                $search_filter = 'displayName='.chr($start + $i).'*';
                $lres = ldap_paged_search($lconn, $ldap_base_dn, $search_filter);
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
			$e = isset($o['userprincipalname']) && isset($o['userprincipalname'][0]) ? strtolower($o['userprincipalname'][0]) : 'missing email item'.$j."-".chr($start+$i);
			$all_users[$e] = $o;//$tmp;
		}
	}
	//Terminated users
	$ldap_base_dn = 'OU=Terminated Users with Active Email,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
	$start = ord('a');
        for($i=0; $i<26; $i++) {
                $search_filter = 'displayName='.chr($start + $i).'*';
                $lres = ldap_paged_search($lconn, $ldap_base_dn, $search_filter);
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
			$e = isset($o['userprincipalname']) && isset($o['userprincipalname'][0]) ? strtolower($o['userprincipalname'][0]) : 'missing email item'.$j."-".chr($start+$i);
			$all_users[$e] = $o;//$tmp;
		}
	}
	//disabled users
	$ldap_base_dn = 'OU=Disabled,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
	$start = ord('a');
        for($i=0; $i<26; $i++) {
                $search_filter = 'displayName='.chr($start + $i).'*';
                $lres = ldap_paged_search($lconn, $ldap_base_dn, $search_filter);
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
			$e = isset($o['userprincipalname']) && isset($o['userprincipalname'][0]) ? strtolower($o['userprincipalname'][0]) : 'missing email item'.$j."-".chr($start+$i);
			$all_users[$e] = $o;//$tmp;
		}
	}
	//for all users get their proxy addresses too.  
	foreach($all_users as $k => $v) {
		$arr = isset($v['proxyaddresses']) && is_array($v['proxyaddresses']) ? $v['proxyaddresses'] : array();
		$tmp = array();
		foreach($arr as $a) {
			$pos = preg_match("/smtp/i", $a);
			if ($pos !== false) {
				$parts = preg_split("/smtp:/i", $a);
				if (count($parts) > 1) {
					$tmpe = strtolower($parts[1]);
					$tmp[$tmpe] = $k;
				}
			}


		}
		$all_users[$k]['email_smtps'] = $tmp;
	}
	return $all_users;
}
function getDisabledUsers() {
	$all_users = array();
	$lconn = ad_bind();
	$ldap_base_dn = '';
	$start = ord('a');
        for($i=0; $i<26; $i++) {
                $search_filter = 'displayName='.chr($start + $i).'*';
                $lres = ldap_paged_search($lconn, $ldap_base_dn, $search_filter);
                $count = $lres['count'];
		for($j=0; $j < $count; $j++) {
			$o = $lres[$j];
			$e = isset($o['userprincipalname']) && isset($o['userprincipalname'][0]) ? strtolower($o['userprincipalname'][0]) : 'missing email item'.$j."-".chr($start+$i);
			$all_users[$e] = $o;//$tmp;
		}
	}
	return $all_users;
}
function getTerminatedUsers() {
	$all_users = array();
	$lconn = ad_bind();
	$ldap_base_dn = 'OU=Terminated Users with Active Email,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
	$start = ord('a');
        for($i=0; $i<26; $i++) {
                $search_filter = 'displayName='.chr($start + $i).'*';
                $lres = ldap_paged_search($lconn, $ldap_base_dn, $search_filter);
                $count = $lres['count'];
		for($j=0; $j < $count; $j++) {
			$o = $lres[$j];
			$e = isset($o['userprincipalname']) && isset($o['userprincipalname'][0]) ? strtolower($o['userprincipalname'][0]) : 'missing email item'.$j."-".chr($start+$i);
			$all_users[$e] = $o;//$tmp;
		}
	}
	return $all_users;
}

/* function findUPNByAnyEmailAddress
 * param &$au the entire AD user tree of users termed and in the OU group
 * param $email - the email you want to find from parsed proxy smtp addresses
 * returns UPN value if a proxy address is found. 
 */
function findUPNByAnyEmailAddress(&$au, $email) {
	foreach($au as $k => $v) {
		foreach($v['email_smtps'] as $ke => $e) {
			if ($ke == $email) {
				return $k;
			}
		}
	}
	return false;
}

/*
 * function getMatchingNodes gets all matching AD nodes and returns them based on matching all strings passed in values in a given key. 
 * param $key is the key you want to match in AD like "distinguishedName" for example
 * param $values is the strings that have to be found in the value of the node
 * param &au is the entire AD tree in memory -> why fetch it twice or bog it down with needless queries
 * returns AD objects or false  - 
 * description iterate over AD tree and find where both values are positive in a given  node and return results where the values are true or false if not found
 */
function getMatchingNodes($key, $values, &$au) {
	$matches = false;
	$vcount = count($values);
	foreach($au as $a) {
		$node = $a[$key];
		$str = isset($node[0]) ? $node[0] : '';
		$str = strtolower($str);
		//echo "ad_value = $str\n";
		$finds = 0;
		foreach($values as $v) {
			$pos = strpos($str, strtolower($v));
			//echo "exponent value = ".strtolower($v)."\n";
			if ($pos !== false) {
				$finds++;
				//echo "found $v finds=$finds\n";
			}
		}
		if ($finds == $vcount) {
			$matches[] = $a;
		}
	}
	return $matches;
}

/*
 * function title_change is to update the title in AD to the passed in value from ExponentHR
 * param &$au is a hashmap of the entire ad tree
 * param $job is the change mapping of the field to update.  
 * param $lconn is the connector to ldap
 * returns true or false if the job completed
 */
function title_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['title'] = array( $job['exponenthr_title']);
		if ($job['exponenthr_title'] == '') {
			$ldaprecord['title'] = array();
		}
		//set the main audit subject
		$audit = 'ldap title update for ';
		//set the user that is being updated in the audit. 
		$audit .= $job['email'];
		//include what the value used to be. 
		$audit .= " changed from '".$job['activedirectory_title']."' to '";
		//include what the value is changed to.
		$audit .= $job['exponenthr_title']."'";
		//log the audit. 
		audit($audit);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	audit('FAIL ldap title update for '.$job['email']." set to ".$job['exponenthr_title']);
	return false;
}

/*
 * function employeeid_change is to update the empoyeeid in AD to the passed in value from ExponentHR
 * employeeid in active directory is going to be an extension attribute called "extensionattribute14"
 * param &$au is a hashmap of the entire ad tree
 * param $job is the change mapping of the field to update.  
 * param $lconn is the connector to ldap
 * returns true or false if the job completed
 */
function employeeid_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['extensionattribute14'] = array( $job['exponenthr_employeeid']);
		audit('ldap employeeid update for '.$job['email']." changed from '".$job['activedirectory_employeeid']."' to ".$job['exponenthr_employeeid']);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	audit('FAIL ldap employeeid update for '.$job['email']." set to ".$job['exponenthr_employeeid']);
	return false;
}
/*
 * function location_change is to update the empoyeeid in AD to the passed in value from ExponentHR
 * employeeid in active directory is going to be an extension attribute called "extensionattribute14"
 * param &$au is a hashmap of the entire ad tree
 * param $job is the change mapping of the field to update.  
 * param $lconn is the connector to ldap
 * returns true or false if the job completed
 */
function location_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['physicaldeliveryofficename'] = array( $job['exponenthr_location']);
		if ($job['exponenthr_location'] == '') {
			$ldaprecord['physicaldeliveryofficename'] = array();
		}
		//set the main audit subject
		$audit = 'ldap location (physicaldeliveryofficename) update for ';
		//set the user that is being updated in the audit. 
		$audit .= $job['email'];
		//include what the value used to be. 
		$audit .= " changed from '".$job['activedirectory_location']."' to '";
		//include what the value is changed to.
		$audit .= $job['exponenthr_location']."'";
		//log the audit. 
		audit($audit);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	return false;
}
/*
 * function department_change is to update the empoyeeid in AD to the passed in value from ExponentHR
 * employeeid in active directory is going to be an extension attribute called "extensionattribute14"
 * param &$au is a hashmap of the entire ad tree
 * param $job is the change mapping of the field to update.  
 * param $lconn is the connector to ldap
 * returns true or false if the job completed
 */
function department_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['department'] = array( $job['exponenthr_department']);
		if ($job['exponenthr_department'] == '') {
			$ldaprecord['department'] = array();
		}
		//set the main audit subject
		$audit = 'ldap department update for ';
		//set the user that is being updated in the audit. 
		$audit .= $job['email'];
		//include what the value used to be. 
		$audit .= " changed from '".$job['activedirectory_department']."' to '";
		//include what the value is changed to.
		$audit .= $job['exponenthr_department']."'";
		//log the audit. 
		audit($audit);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	audit('FAIL ldap department update for '.$job['email']." set to ".$job['exponenthr_department']);
	return false;
}
/*
 * function manager_change is to update the empoyeeid in AD to the passed in value from ExponentHR
 * employeeid in active directory is going to be an extension attribute called "extensionattribute14"
 * param &$au is a hashmap of the entire ad tree
 * param $job is the change mapping of the field to update.  
 * param $lconn is the connector to ldap
 * returns true or false if the job completed
 */
function manager_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['manager'] = array( $job['exponenthr_manager']);
		if ($job['exponenthr_manager'] == '') {
			$ldaprecord['manager'] = array();
		}
		//set the main audit subject
		$audit = 'ldap manager update for ';
		//set the user that is being updated in the audit. 
		$audit .= $job['email'];
		//include what the value used to be. 
		$audit .= " changed from '".$job['activedirectory_manager']."' to '";
		//include what the value is changed to.
		$audit .= $job['exponenthr_manager']."'";
		//log the audit. 
		audit($audit);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	audit('FAIL ldap manager update for '.$job['email']." set to ".$job['exponenthr_manager']);
	return false;
}

function division_change(&$au, $job, $lconn) {
	$dn = $au[$job['email']]['dn'];
	$jq = isQualityJob($au, $job);
	if (isQualityJob($au, $job) === true) {	
		//print_r($job);
		$ldaprecord['company'] = array( $job['exponenthr_division']);
		if ($job['exponenthr_division'] == '') {
			$ldaprecord['company'] = array();
		}
		//set the main audit subject
		$audit = 'ldap division update for ';
		//set the user that is being updated in the audit. 
		$audit .= $job['email'];
		//include what the value used to be. 
		$audit .= " changed from '".$job['activedirectory_division']."' to '";
		//include what the value is changed to.
		$audit .= $job['exponenthr_division']."'";
		//log the audit. 
		audit($audit);
		$add = ldap_mod_replace($lconn, $dn, $ldaprecord);
		return true;
	}
	audit('FAIL ldap division update for '.$job['email']." set to ".$job['exponenthr_division']);
	return false;
}

function audit($msg) {
	$dt = date('Y-m-d H:i:s');
	$msg = trim($msg);
	global $audit_log;
	if($audit_log == '') {
		die('set $audit_log filename in ../.createUsersConfig.php'."\n");
	}
	file_put_contents($audit_log, "$dt ExponentSync tool $msg\n", FILE_APPEND);
}

function isQualityJob($au, $job) {
	$earr = true;	
	$tmparr = array();
	$dn = $au[$job['email']]['dn'];
	if ($dn == '') {
		$tmparr[] = "QUALITY CHECK FAIL dn for email {$job['email']} not found in au";
	}
	if (count($tmparr)) {
		return $tmparr;
	}
	return $earr;
}

?>
