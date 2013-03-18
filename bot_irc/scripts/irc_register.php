<?php

class irc_register implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Registers your account for more irc access. Usage: |g|.z register|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		Db::execute("insert ignore into zz_irc_access (name, host) values (:name, :host)",
			array(":name" => $nick, ":host" => $uhost));
		return "You have been registered.";
	}
    public function isHidden() { return false; }
}
