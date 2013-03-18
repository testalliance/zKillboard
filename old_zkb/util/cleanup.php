<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";

for ($year = 2003; $year<= 2012; $year++) {
	for ($month = 1; $month <= 53; $month++) {
		$month0 = $month < 10 ? "0$month" : $month;
		echo "$year $month\n";
		Db::execute("drop table if exists {$dbPrefix}items_{$year}_{$month0}");
		Db::execute("drop table if exists {$dbPrefix}participants_{$year}_$month0");
		Db::execute("drop table if exists {$dbPrefix}kills_{$year}_$month0");
	}
}

Db::execute("update zz_killmail set processed = 'N'");
