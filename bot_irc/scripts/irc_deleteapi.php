<?php

class irc_deleteapi implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "Delete an API.  Usage: |g|.z deleteapi <keyid>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$keyID = (int) $parameters[0];
		if ($keyID == 0) irc_error("Please provide a valid keyID.");
		$info = Db::queryRow("select * from zz_api where keyID = :k", array(":k" => $keyID), 0);
		if ($info == null) irc_error("Could not find KeyID $keyID");

		Db::execute("delete from zz_api where keyID = :k", array(":k" => $keyID));
		Db::execute("delete from zz_api_characters where keyID = :k", array(":k" => $keyID));

		return "|g|$keyID|n| deleted.  He was my friend, I'm sorry to see him go... *sob*";
	}
    public function isHidden() { return false; }
}
