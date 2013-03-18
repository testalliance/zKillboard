<?php

class irc_random implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		if (sizeof($parameters) == 0 || $parameters[0] == "") return rand(1, 100);
		else {
			$num = (int) $parameters[0];
			return rand(1, $num);
		}
	}
    public function isHidden() { return true; }
}
