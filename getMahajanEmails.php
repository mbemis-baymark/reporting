<?php
// Script derived from getAllJobTitlesCSV.php
// Outputs all employee emails that report up to crothenbuhler@baymark.com
// and are associated with the Mahajan division.

function readCSV($filename) {
    $data = [];
    if (($handle = fopen($filename, 'r')) !== false) {
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        while (($line = fgets($handle)) !== false) {
            $row = str_getcsv($line, ',', "'");
            if (count($row) == count($headers)) {
                $row = array_map(fn($v) => trim(trim($v, "'")), $row);
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

function buildHierarchy($employees) {
    $lookup = [];
    $tree = [];

    foreach ($employees as $emp) {
        if (!isset($emp['employee_email'])) {
            continue;
        }
        $email = strtolower(trim($emp['employee_email']));
        $emp['subordinates'] = [];
        $lookup[$email] = $emp;
    }

    foreach ($lookup as $email => &$emp) {
        if (!empty($emp['supervisor_email'])) {
            $super = strtolower(trim($emp['supervisor_email']));
            if (isset($lookup[$super])) {
                $lookup[$super]['subordinates'][$email] = &$emp;
            } else {
                $tree[$email] = &$emp;
            }
        } else {
            $tree[$email] = &$emp;
        }
    }

    return $tree;
}

function findNode($tree, $email) {
    $email = strtolower(trim($email));
    if (isset($tree[$email])) {
        return $tree[$email];
    }
    foreach ($tree as $node) {
        if (!empty($node['subordinates'])) {
            $found = findNode($node['subordinates'], $email);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

function gatherEmails($node, $divisionMatch, &$emails = []) {
    $division = isset($node['division']) ? strtolower($node['division']) : '';
    if (strpos($division, strtolower($divisionMatch)) !== false) {
        $emails[] = strtolower($node['employee_email']);
    }
    foreach ($node['subordinates'] as $sub) {
        gatherEmails($sub, $divisionMatch, $emails);
    }
    return $emails;
}

$filename = 'allstaff.csv';
$employees = readCSV($filename);
$hierarchy = buildHierarchy($employees);
$manager = 'crothenbuhler@baymark.com';
$manager = strtolower($manager);
$emails = [];

print_r($hierarchy);
$branch = findNode($hierarchy, $manager);
if ($branch !== null) {
    $emails = gatherEmails($branch, 'Mahajan');
} else {
    echo "$manager not found\n";
}
echo implode("\n", $emails);

?>
