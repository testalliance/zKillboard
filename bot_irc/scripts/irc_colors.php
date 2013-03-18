<?php

class irc_colors implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		global $colors;
		$msg = "";
		foreach($colors as $color=>$value) {
			$color = str_replace("|", "", $color);
			$msg .= "$value$color ";
		}
		return $msg;
	}
    public function isHidden() { return true; }
}
