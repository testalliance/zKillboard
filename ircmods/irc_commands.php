<?php

class irc_commands implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Returns a list of available commands. Usage: |g|.z commands|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$commands = array();
		$dir = __DIR__;
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					$s1 = split("irc_", $entry);
					$s2 = split(".php", $s1[1]);
					if (sizeof($s2) == 2) {
						require_once "$dir/$entry";
						$command = $s2[0];
						$className = "irc_$command";
						$class = new $className();
						if (is_a($class, "ircCommand")) {
							$accessLevel = $class->getRequiredAccessLevel();
							if ($nickAccessLevel >= $accessLevel && !$class->isHidden()) $commands[] = $command;
						}
					}
				}
			}
			closedir($handle);
		}
		sort($commands);
		irc_out("|g|your available commands:|n| " . implode(", ", $commands));
	}
    public function isHidden() { return false; }
}
