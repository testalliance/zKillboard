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
