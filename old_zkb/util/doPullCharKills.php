<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
include_once "$base/../init.php";

include_once "apipull.php";

$apiKey = null;
if (isset($argv[1])) $apiKey = $argv[1];

$job = "doPullCharKills";
if (Db::execute("insert into cronlock values ('$job', current_timestamp)", array(), false) === false) exit;

try {
	if (getLoad() < 5) $job($apiKey);
} catch (Exception $ex) {
	echo "error...\n";
	print_r($ex);
}
Db::execute("delete from cronlock where a = '$job'");


function getLoad() {
        $output = array();
        $result = exec("cat /proc/loadavg", $output);

        $split = explode(" ", $result);
        $load = $split[0];
        return $load;
}
