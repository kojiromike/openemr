<?php
/**
 * Configure the site's connection to the database, either by setting the credentials in the environment
 * or by using setup.php
 */

const _SQLCONF_SENTINEL = '_SQLCONF_SENTINEL';
global $disable_utf8_flag;
global $sqlconf;

$disable_utf8_flag = false;

$sqlconfDefaults = [
    "port" => "3306",
    "db_encoding" => "utf8mb4",
];

$sqlconf = [
    "host" => getenv("OPENEMR_MYSQL_HOST") ?? _SQLCONF_SENTINEL,
    "port" => getenv("OPENEMR_MYSQL_PORT") ?? _SQLCONF_SENTINEL,
    "login" => getenv("OPENEMR_MYSQL_USER") ?? _SQLCONF_SENTINEL,
    "pass" => getenv("OPENEMR_MYSQL_PASS") ?? _SQLCONF_SENTINEL,
    "dbase" => getenv("OPENEMR_MYSQL_DBNAME") ?? _SQLCONF_SENTINEL,
    "db_encoding" => getenv("OPENEMR_MYSQL_ENCODING") ?? _SQLCONF_SENTINEL
];

// If all the values in $sqlconf are _SQLCONF_SENTINEL, then set $config = 0 to trigger setup.php.
// Otherwise, if any value is _SQLCONF_SENTINEL, produce an error message indicating that all
// values must be provided in the environment.
$missingRequired = array();
$config = 0;
foreach ($sqlconf as $key => $value) {
    if ($value !== _SQLCONF_SENTINEL) {
        // If any value is not _SQLCONF_SENTINEL,
        // we can expect all required values to be set.
        $config = 1;
    } elseif (array_key_exists($key, $sqlconfDefaults)) {
        // If the key exists in $sqlconfDefaults, it is not required.
        // Set it to the default value
        $sqlconf[$key] = $sqlconfDefaults[$key];
    } else {
        $missingRequired[] = $key;
    }
}

if ($config === 0 || empty($missingRequired)) {
    // Either all required values are set
    // or all values are _SQLCONF_SENTINEL and we need setup.php
    // either way we're done here.
    return;
}

die("Please provide these missing values in the environment: " . implode(", ", $missingRequired));
