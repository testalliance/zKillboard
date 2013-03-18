<?php

class irc_killshour implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Reports the number of kills in the last hour. Usage: |g|.z stats|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$kills = Db::queryField("select contents from zz_storage where locker = 'KillsLastHour'", "contents");
		$message = "Kills in the last hour: |g|" . number_format($kills);

		irc_out($message);
	}
    public function isHidden() { return false; }
}
