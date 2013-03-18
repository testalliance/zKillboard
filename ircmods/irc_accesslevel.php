<?php

class irc_accesslevel implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Retrieves your accesslevel with this bot. Usage: |g|.z accesslevel|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$pName = trim(implode(" ", $parameters));
		if (strlen($pName)) irc_error("|r|You must be $pName to check access level.  Have them type: |g|.z accesslevel");
		$accessLevel = Db::queryField("select accessLevel from zz_irc_access where name = :name and host = :host", "accessLevel",
			array(":name" => $nick, ":host" => $uhost), 0);
		if ($accessLevel === null) irc_out("|r|You are not registered.|n|");
		else irc_out("|g|Your access level is:|n| $accessLevel");
	}

	public function isHidden() { return false; }
}
