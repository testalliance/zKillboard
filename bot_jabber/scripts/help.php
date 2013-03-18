<?php

class jab_help implements jabCommand {
	public function getDescription() {
		return "Returns the description of a command. Usage: .help <command>";
	}

	public function execute($nick, $command, $parameters) {
		if (sizeof($parameters) == 0) return "Usage: .help <command> To see the list of commands: .commands";
		$command = $parameters[1];
		$base = __DIR__;
		$fileName = "$base/$command.php";
		if (!file_exists($fileName)) return "Unknown command: $command";

		require_once $fileName;
		$className = "jab_$command";
		$class = new $className();
		if (!is_a($class, "jabCommand")) return "Module $command does not implement interface ircCommand!";
		$dscr = $class->getDescription();
		return "$command: $dscr";
	}
}
