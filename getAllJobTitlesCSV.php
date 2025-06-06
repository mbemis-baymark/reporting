<?php

function readCSV($filename) {
    $data = array();
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers); // Trim whitespace from headers

        while (($line = fgets($handle)) !== FALSE) {
            $row = str_getcsv($line, ",", "'"); // Handle single-quoted fields properly

            if (count($row) == count($headers)) {
                $row = array_map(fn($value) => trim(trim($value, "'")), $row); // Remove outer single quotes
                $data[] = array_combine($headers, $row);
            } else {
                error_log("Skipping malformed row: " . implode(", ", $row));
            }
        }
        fclose($handle);
    }
    return $data;
}

function buildHierarchy($employees, $group_presidents) {
    $tree = [];
    $lookup = [];
    
    foreach ($employees as $employee) {
	    if(!isset($employee['employee_email'])) {
		    continue;//ignore for now
		    print_r($employee);
		    die("employee without a manager");
	    }
        $email = strtolower(trim($employee['employee_email']));
        $supervisor = strtolower(trim($employee['supervisor_email']));
        $lookup[$email] = $employee;
        $lookup[$email]['subordinates'] = [];
    }
    
    // Ensure Group Presidents exist in lookup and set them as roots
    foreach ($group_presidents as $email => $title) {
        $email = strtolower(trim($email));
        if (!isset($lookup[$email])) {
            $lookup[$email] = [
                "employee_email" => $email,
                "job_title" => $title,
                "supervisor_email" => null,
                "subordinates" => []
            ];
        }
        $tree[$email] = &$lookup[$email]; // Ensure Group Presidents are in tree root
    }
    
    foreach ($lookup as $email => &$employee) {
        if (!empty($employee['supervisor_email'])) {
            $supervisor_email = strtolower(trim($employee['supervisor_email']));
            if (isset($lookup[$supervisor_email])) {
                $lookup[$supervisor_email]['subordinates'][$email] = &$employee;
            }
        }
    }
    
    return $tree;
}

function countEmployees($node) {
    $count = 0;
    foreach ($node['subordinates'] as $subordinate) {
        $count += 1 + countEmployees($subordinate);
    }
    return $count;
}

function getTitlesByLevel(&$node, $level = 1, &$titlesByLevel = []) {
    if (!isset($titlesByLevel[$level])) {
        $titlesByLevel[$level] = [];
    }
    
    if (!isset($titlesByLevel[$level][$node['job_title']])) {
        $titlesByLevel[$level][$node['job_title']] = 0;
    }
    $titlesByLevel[$level][$node['job_title']]++;
    
    foreach ($node['subordinates'] as $subordinate) {
        getTitlesByLevel($subordinate, $level + 1, $titlesByLevel);
    }
    
    // Sort titles alphabetically at each level by key
    foreach ($titlesByLevel as &$titles) {
        ksort($titles);
    }
    
    return $titlesByLevel;
}

// Load CSV data
$filename = "allstaff.csv";
$employees = readCSV($filename);

// Define Group Presidents
$group_presidents = [
    "smadrid@baymark.com" => "RTC Group President",
    "ttorrente@specialcarecorp.com" => "SpecialCare Group President",
    "crothenbuhler@baymark.com" => "OBOT Group President",
    "ptrisvan@baymark.com" => "OTP Group President",
    "bfinn@baymark.com" => "SVP RCM"
];

// Build hierarchy
$hierarchy = buildHierarchy($employees, $group_presidents);

$results = [];

foreach ($group_presidents as $email => $title) {
    if (isset($hierarchy[$email])) {
        $employee_count = countEmployees($hierarchy[$email]);
        $titles_by_level = getTitlesByLevel($hierarchy[$email]);

        $results[$title] = [
            "Total Employees" => $employee_count,
            "Job Titles by Level" => $titles_by_level
        ];
    }
}

// Output JSON result
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
?>

