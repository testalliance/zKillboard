<?php

class irc_source implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Retrieve the source of a killmail. |g|.z source killID";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$keyIDs = array();
		$id = implode(" ", $parameters);
		$source = Db::queryField("select source from zz_killmails where killID = :id", "source", array(":id" => $id));
		if (!$source) irc_out("|r|killID $id not found");
		else irc_out("$id - |g|$source");
	}
    public function isHidden() { return false; }
}
