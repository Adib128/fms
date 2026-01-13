<?php
require_once 'config.php';

$_POST['draw'] = 1;
$_POST['start'] = 0;
$_POST['length'] = 5;
$_POST['search'] = ['value' => ''];
$_POST['order'] = [['column' => 0, 'dir' => 'desc']];

ob_start();
include 'liste_vidange_json.php';
$output = ob_get_clean();

// Check if it's valid JSON
$json = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'JSON Error: ' . json_last_error_msg() . PHP_EOL;
    echo 'Raw output: ' . substr($output, 0, 500) . PHP_EOL;
} else {
    echo 'JSON is valid' . PHP_EOL;
    echo 'Records Total: ' . ($json['recordsTotal'] ?? 'N/A') . PHP_EOL;
    echo 'Records Filtered: ' . ($json['recordsFiltered'] ?? 'N/A') . PHP_EOL;
    echo 'Data Count: ' . count($json['data'] ?? []) . PHP_EOL;
    echo 'First record: ' . print_r($json['data'][0] ?? 'No data', true) . PHP_EOL;
}
?>
