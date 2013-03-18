<?php

class irc_errorcode implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Lookup the meaning of an API error code.  Usage: |g|.z apierror <code>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$errorCode = (int) $parameters[0];
		if ($errorCode == 0) irc_error("|r|Please provide a valid errorCode.|n|");

		$key = "api_error:$errorCode";
		$msg = Db::queryField("select contents from zz_storage where locker = :c", "contents", array(":c" => $key));

		if ($msg == null) irc_error("|r|Unable to locate error message for error code $errorCode |n|");
		irc_out("|g|Error $errorCode:|n| $msg");
	}
    public function isHidden() { return false; }
}
