<?php

class irc_<namehere> implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		irc_out("Woof!");
	}
    public function isHidden() { return false; }
}
