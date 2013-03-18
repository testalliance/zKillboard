<?php

class jab_commands implements jabCommand {
	public function getDescription() {
		return "Returns a list of available commands. Usage: .commands";
	}

	public function execute($nick, $command, $parameters) {
		$commands = array();
		$dir = __DIR__;
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					$s1 = explode("irc_", $entry);
					$s2 = explode(".php", $s1[0]);
					if (sizeof($s2) == 2) {
						require_once "$dir/$entry";
						$command = $s2[0];
						$className = "jab_$command";
						$class = new $className();
						if (is_a($class, "jabCommand")) {
                            $commands[] = $command;
						}
					}
				}
			}
			closedir($handle);
		}
		sort($commands);
		return "Available commands: " . implode(", ", $commands);
	}
}
