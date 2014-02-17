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

class irc_help implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Returns the description of a command. Usage: |g|.z help <command>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		if (sizeof($parameters) == 0 || $parameters[0] == "") irc_error("Usage: |g|.z help <command>|n| To see the list of commands: |g|.z commands|n|");
		$command = $parameters[0];
		$base = __DIR__;
		$fileName = "$base/irc_$command.php";
		if (!file_exists($fileName)) irc_error("|r|Unknown command: $command |n|");

		require_once $fileName;
		$className = "irc_$command";
		$class = new $className();
		if (!is_a($class, "ircCommand")) irc_error("|r|Module $command does not implement interface ircCommand!|n|");
		$dscr = $class->getDescription();
		irc_out("$command: $dscr");
	}
    public function isHidden() { return false; }
}
