<?php

class irc_stats implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Lists the number of kills in the database. Usage: |g|.z stats|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$message = "";

		$unParsed = Db::queryField("select count(*) count from zz_killmails where processed = 0", "count", array(), 0);
		$message .= "|g|Unparsed kills:|n| " . number_format($unParsed) . " / ";

		$totalCount = Db::queryField("select count(*) count from zz_killmails", "count", array(), 000);
		$message .= "|g|Total kills:|n| " . number_format($totalCount) . " / ";

		$killCount = Db::queryField("select count(*) count from zz_killmails where processed = 1", "count", array(), 000);
		$message .= "|g|Actual Kills:|n| " . number_format($killCount) . " / ";

		$userCount = Db::queryField("select count(*) count from zz_users", "count", array(), 000);
		$message .= "|g|Users:|n| " . number_format($userCount) . " / ";

		$apiCount = Db::queryField("select count(*) count from zz_api where errorCode = 0", "count", array(), 000);
		$message .= "|g|Valid APIs:|n| " . number_format($apiCount);

		irc_out($message);
	}
    public function isHidden() { return false; }
}
