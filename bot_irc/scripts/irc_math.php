<?php

class irc_math implements ircCommand {
	public function getRequiredAccessLevel() {
		return 1;
	}

	public function getDescription() {
		return ".z math |g|Does math, duh";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$exp = implode(" ", $parameters);
		eval('$o = ' . preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $exp) . ';');
		return $o;
	}
    public function isHidden() { return true; }
}
