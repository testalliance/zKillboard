<?php

class irc_reprice implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "Recalculates the ISK value and points of a kill. Usage: |g|.z reprice <killID>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$killID = (int) $parameters[0];
		if ($killID == 0) irc_error("|r|Please provide a valid killID.");
		$count = Db::queryField("select count(*) count from zz_participants where killID = :killID", "count", array(":killID" => $killID));
		if ($count == 0) return "|r|KillID $killID does not exist!";

		$total = Price::updatePrice($killID);
		$points = Points::updatePoints($killID);
		return "|g|$killID|n| repriced to|g|" . number_format($total, 2) . "|n| ISK and |g|" . number_format($points, 0) . "|n| points";
	}
    public function isHidden() { return false; }
}
