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
