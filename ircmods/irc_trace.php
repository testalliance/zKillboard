<?php

class irc_trace implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Shows the stacktrace for an error. Usage: |g|.z trace <hash>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
        $trace = Db::queryField("SELECT message FROM zz_errors WHERE id = :hash", "message", array(":hash" => implode(",", $parameters)));
        if(sizeof($trace) == 0)
        {
            irc_out("|r|Unable to find a stacktrace with that id|n|");
        }
        else
        {
            irc_out("|g|StackTrace found.|n| http://zkillboard.com/stacktrace/" . implode(",", $parameters) ."/ |g|Error:|n| $trace");
        }
	}
    public function isHidden() { return false; }
}
