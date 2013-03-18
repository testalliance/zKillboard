<?php

class irc_cleanapi implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "Removes all 203, 220, 221, and 222 error codes.  Usage: |g|.z cleanapi";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$removed = Db::execute("delete from zz_api where errorCode in (203, 220)");
		$removed = number_format($removed, 0);
		irc_out("APIs with errorCode 203 and 220 have been removed from the database.  Good riddance to |g|$removed|n| troublesome keys...");
	}

    public function isHidden() { return false; }
}
