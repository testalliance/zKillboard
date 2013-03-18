<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";

include_once "apipull.php";

$job = "maintenance";
//if (Db::execute("insert into cronlock values ('$job', current_timestamp)", array(), false) === false) die('exiting');

$tables = array();
$result = Db::query("show tables");
foreach($result as $row) {
	foreach($row as $column) {
		$tables[] = $column;
	}
}

$tableIsGood = array("OK", "Table is already up to date", "The storage engine for the table doesn't support check");
$count = 0;
foreach ($tables as $table) {
	$count++;
	echo "$count/" . sizeof($tables) . ": $table ... ";
	//echo " converting ...";
	//Db::execute("alter table $table engine = myisam");
	echo " checking ... ";
	$result = Db::queryRow("check table $table");
	if (!in_array($result["Msg_text"], $tableIsGood)) {
		echo " reparing ... ";
		Db::execute("repair table $table");
	} 
	echo " analyzing ... ";
	Db::execute("analyze table $table");
	echo " done.\n";
}
/*
for ($year = 2003; $year <= date("Y"); $year++) {
	for ($week = 0; $week <= 53; $week++) {
		$result1 = Db::execute("update zz_killmail z left join zz_kills k on (z.killid = k.killid) left join zz_participants p on (k.killid = p.killid) set processed = 0 where k.year = $year and k.week = $week and p.year is null and p.week is null and processed = 1 and p.killid is null");
		$result2 = Db::execute("update zz_killmail z left join zz_kills k on (z.killid = k.killid) left join zz_items p on (k.killid = p.killid) set processed = 0 where k.year = $year and k.week = $week and p.year is null and p.week is null and processed = 1 and p.killid is null");
		$result3 = Db::execute("update zz_killmail z left join zz_kills k on (z.killid = k.killid) set processed = 0 where k.year = $year and k.week = $week and processed = 1 and k.killid is null");
		if ($result1 != 0 || $result2 != 0 || $result3 != 0) echo "$year/$week: $result1 $result2 $result3\n";
	}
}
*/
Db::execute("delete from cronlock where a = '$job'");

