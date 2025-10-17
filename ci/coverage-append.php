<?php

/**
 * Auto-append file for E2E test coverage collection.
 * This file is automatically included after every PHP script execution
 * when E2E coverage is enabled.
 */

define('WEBROOT', dirname(__DIR__));
define('COVERAGE_DIR', '/tmp/openemr-coverage');
define('E2E_COVERAGE_DIR', COVERAGE_DIR . '/e2e');

// Write marker to prove this file executes
$marker = '/tmp/openemr-coverage-APPEND_EXECUTED';
$data = date('Y-m-d H:i:s') . " - append executed\n";
if (file_put_contents($marker, $data, FILE_APPEND | LOCK_EX) === false) {
    error_log("COVERAGE DEBUG: Failed to write append marker to $marker");
}

if (!function_exists('xdebug_get_code_coverage')) {
    error_log("Append: Required function xdebug_get_code_coverage is missing");
}

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

if (empty($coverage)) {
    error_log("Coverage is unexpectedly empty");
    return;
}

// Create unique filename based on request time and random component
if (!is_dir(E2E_COVERAGE_DIR)) {
    @mkdir(E2E_COVERAGE_DIR, 0777, true);
}

$filename = sprintf(
    '%s/coverage.e2e.%s.%s.raw.php',
    E2E_COVERAGE_DIR,
    date('YmdHis'),
    bin2hex(random_bytes(8))
);

// Save the raw Xdebug coverage data directly to avoid memory exhaustion
// This will be processed later when merging coverage files
// Format: just the raw array from xdebug_get_code_coverage()
$exported = var_export($coverage, true);
file_put_contents($filename, "<?php\nreturn " . $exported . ";\n");
