<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
					$s1 = explode("irc_", $entry);
					$s2 = explode(".php", $s1[1]);
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
