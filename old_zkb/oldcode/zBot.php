<?php

$commandLineExecution = "cli" == php_sapi_name();
set_time_limit(0);
if (!$commandLineExecution)
{
		return;
}
include_once "init.php";

Log::log(implode(" ", $argv));

$nick = $argv[1];
$channel = $argv[2];
$command = $argv[3];
$split = explode(" ", $command);
$command = $split[0];
$split = array_slice($split, 1);
$argv[4] = implode(" ", $split);

Log::log("IRC command: $nick $channel $command $argv[4]");

$red = "\x03" . "05";
$green = "\x03" . "03";
$reset = "\x03";

switch ($command) {
		default:
				case "help":
						chat($channel, "Prefix all commands with .who   Available commands: add, alli, corp, stats, status, who");
				break;
				case "stats":
				case "stat":
					$killCount = Db::queryField("select count(killID) count from zz_kills", "count");
					chat($green . number_format($killCount) . " $reset kills.");
						break;
}

function securityCheck($channel, $nick) {
		if (in_array($nick, array("Squizz_C", "Karbowiak","FlyBoy","Beansman","petllama"))) return;
		chat($channel, "$nick: Your authorization fails clearance.  Please see your nearest TSA agent.");
		exit;
}

function combineRemainingArgs($args) {
		$result = "";
		for ($i = 4; $i < sizeof($args); $i++) {
				if ($i > 4) $result .= " ";
				$result .= $args[$i];
		}
		return $result;
}

function chat($channel, $text) {
		if ($channel == "" || $text == "" ) return;
		$channel = substr($channel, 1);
		error_log("Who - $text\n", 3, "/var/www/bin/eggdrop/$channel.log");
		flush();
}
