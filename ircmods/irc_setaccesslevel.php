<?php

class irc_setaccesslevel implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "Sets the access level to someone's account.  Usage: |g|.z setaccesslevel <user> <level>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		if (sizeof($parameters) != 2) irc_error("Usage: |g|.z setaccesslevel <user> <level>|n|");
		$user = $parameters[0];
		$level = (int) $parameters[1];
		if ($level > $nickAccessLevel) irc_error("|r|Cannot set an accesslevel higher than your own, which is $nickAccessLevel |n|");
		$i = Db::execute("update zz_irc_access set accessLevel = :al where name = :user", array(":al" => $level, ":user" => $user));
		if ($i > 0) irc_out("$user now has access level of $level");
		else irc_out("Either they already have that accesslevel or no one with the name $user found.  Are they registered? Or have them execute: |g|.z accesslevel|n|");
	}
    public function isHidden() { return false; }
}
