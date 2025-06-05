<?php
include_once '../.createUsersConfig.php';
function send_email($to, $subject, $body) {
	global $email_api_key;
	$secretKey = $email_api_key;//"your_secret_key_here";
	$todayDate = date("F j, Y", strtotime(gmdate('Y-m-d H:i:s')));
	$authKey = generateAuthKey($secretKey, $todayDate);

	// Replace with the actual API URL
	$apiUrl = 'https://taskautomationhub.baymark.com/Utility/SendMail';

	// Replace with the actual recipients, subject, and body
	$recipients = $to;//["email1@example.com", "email2@example.com"];
	//$subject = "Your Subject";
	//$body = "Your Email Body";

	$data = [
		"recipients" => $recipients,
		"subject" => $subject,
		"body" => $body,
	];

	$ch = curl_init($apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Authorization: " . $authKey,
		"Content-Type: application/json"
	]);

	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'Error: ' . curl_error($ch);
	} else {
		//echo $response;
	}

	curl_close($ch);
}

function generateAuthKey($input1, $input2)
{
	$result = '';
	$length1 = strlen($input1);
	$length2 = strlen($input2);

	for ($i = 0; $i < $length1 || $i < $length2; $i++) {
		$c1 = $i < $length1 ? $input1[$i] : chr(0);
		$c2 = $i < $length2 ? $input2[$i] : chr(0);
		$result .= chr(ord($c1) ^ ord($c2));
	}

	return base64_encode($result);
}
?>
