<?php

class irc_deletekill implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$killID = (int) $parameters[0];
		if ($killID >= 0) irc_error("|r|Can only delete manually posted killmails with a killID < 0");
		// Verify the kill exists
		$count = Db::execute("select count(*) count from zz_killmails where killID = :killID", array(":killID" => $killID));
		if ($count == 0) irc_error("|r|killID $killID not found.");
		// Remove it from the stats
		Stats::calcStats($killID, false);
		// Remove it from the kill tables
		Db::execute("delete from zz_participants where killID = :killID", array(":killID" => $killID));
		Db::execute("delete from zz_items where killID = :killID", array(":killID" => $killID));
		// Mark the kill as deleted
		Db::execute("update zz_killmails set processed = 2 where killID = :killID", array(":killID" => $killID));
		irc_out("killID |g|$killID|n| has been deleted.");
	}
    public function isHidden() { return false; }
}
