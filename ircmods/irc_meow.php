<?php

class irc_meow implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		irc_out("Woof!");
	}
    public function isHidden() { return true; }
}
