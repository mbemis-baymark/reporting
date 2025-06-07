<?php
$script = __DIR__ . '/../getMahajanEmails.php';
$output = [];
$returnVar = 0;
exec('php ' . escapeshellarg($script), $output, $returnVar);
if ($returnVar !== 0) {
    fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
    exit($returnVar);
}
$emails = array_values(array_filter(array_map('trim', $output), function($line) {
    return filter_var($line, FILTER_VALIDATE_EMAIL);
}));
$expected = ['crothenbuhler@baymark.com', 'employee1@company.com'];
if ($emails !== $expected) {
    fwrite(STDERR, 'Unexpected output: ' . json_encode($emails) . PHP_EOL);
    exit(1);
}
echo "Mahajan script returned expected emails." . PHP_EOL;
?>
