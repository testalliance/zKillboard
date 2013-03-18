<?php

class irc_help implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Returns the description of a command. Usage: |g|.z help <command>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		if (sizeof($parameters) == 0 || $parameters[0] == "") return "Usage: |g|.z help <command>|n| To see the list of commands: |g|.z commands|n|";
		$command = $parameters[0];
		$base = __DIR__;
		$fileName = "$base/irc_$command.php";
		if (!file_exists($fileName)) return "|r|Unknown command: $command |n|";

		require_once $fileName;
		$className = "irc_$command";
		$class = new $className();
		if (!is_a($class, "ircCommand")) return "|r|Module $command does not implement interface ircCommand!|n|";
		$dscr = $class->getDescription();
		return "$command: $dscr";
	}
    public function isHidden() { return false; }
}
