<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";

require_once "apipull.php";

$job = "doPullCorpKills";
if (Db::execute("insert into cronlock values ('$job', current_timestamp)", array(), false) === false) exit;

try {
	if (getLoad() < 5) $job();
} catch (Exception $ex) {
	// blah
}
Db::execute("delete from cronlock where a = '$job'");

function getLoad() {
        $output = array();
        $result = exec("cat /proc/loadavg", $output);

        $split = explode(" ", $result);
        $load = $split[0];
        return $load;
}
