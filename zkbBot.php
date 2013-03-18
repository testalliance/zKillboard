<?php

$base = dirname(__FILE__);
require_once "$base/init.php";

// zkbBot.php "$nick" "$uhost" "$chan" "$args2"
$nick = $argv[1];
$uhost = $argv[2];
$channel = $argv[3];
$commands = $argv[4];

$params = split(" ", trim($commands));
$command = $params[0];
array_shift($params);

try {
	$fileName = "$base/ircmods/irc_$command.php";
	if (!file_exists($fileName)) irc_error("Unknown command: $command");

	require_once $fileName;
	$className = "irc_$command";
	$class = new $className();
	if (!is_a($class, "ircCommand")) irc_error("Module $command does not implement interface ircCommand!");

	$accessLevel = Db::queryField("select accessLevel from zz_irc_access where name = :name and host = :host", "accessLevel",
			array(":name" => $nick, ":host" => $uhost), 0);
	if ($accessLevel === null) $accessLevel = 0;
	if ($accessLevel < $class->getRequiredAccessLevel()) irc_error("You do not have access to the $command command.");

	$params = implode(" ", $params);

	$params = trim($params);
	$params = explode(" ", $params);
	irc_log($nick, $uhost, $command, $params);
	$class->execute($nick, $uhost, $channel, $command, $params, $accessLevel);
} catch (Exception $ex) {
	irc_error("$command ended with error: " . $ex->getMessage());
}

function irc_log($nick, $uhost, $command, $params)
{
	$id = Db::queryField("SELECT id FROM zz_irc_access WHERE name = :nick AND host = :uhost", "id", array(":nick" => $nick, ":uhost" => $uhost));
	if ($id == null) $id = 0;
	Db::execute("INSERT INTO zz_irc_log (id, nick, command, parameters) VALUES (:id, :nick, :command, :params)", array(":nick" => $nick, ":id" => $id, ":command" => $command, ":params" => implode(" ", $params)));
}

function irc_error($text) {
	$text = Log::addIRCColors($text);
	irc_out($text);
	die();
}

function irc_out($text) {
	global $nick, $channel;
	$text = Log::addIRCColors($text);
	if ($channel != "#") error_log("PRIVMSG $channel :$nick: $text\n", 3, "/var/killboard/bot/commands.txt");
	else error_log("PRIVMSG $nick :$text\n", 3, "/var/killboard/bot/commands.txt");
}

interface ircCommand {
	public function getRequiredAccessLevel();
	public function getDescription();
	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel);
	public function isHidden();
}
