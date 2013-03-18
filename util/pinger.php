<?php
$base = dirname(__FILE__);
require_once "$base/../init.php";

try {
	Db::query("select now()", array(), 0);
} catch (Exception $ex) {
	Log::irc("|r|Unable to connect to the database: " . $ex->getMessage());
}
