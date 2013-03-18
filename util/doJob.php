<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/cron.php";


if (!isset($argv[1])) die("Must provide a job name");

set_time_limit(600);

$job = $argv[1];
$job();
