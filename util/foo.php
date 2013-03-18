<?php

require_once "../init.php";

$result = Db::query("SELECT table_name, engine, row_format FROM information_schema.TABLES where TABLE_SCHEMA = 'zkillboard' and engine = 'InnoDB' and row_format != 'compressed'", array(), 0);

foreach($result as $row) {
	$table = $row["table_name"];
	echo "$table\n";
	Db::execute("alter table $table engine=innodb row_format=compressed");
}
