<?php

class irc_die implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		irc_out("Fuck you you little bitch.  You die!");
	}
    public function isHidden() { return true; }
}
