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

class irc_maintenance implements ircCommand {
	public function getRequiredAccessLevel() {
		return 6;
	}

	public function getDescription() {
		return "Returns maintenance mode as well as sets or disables maintenance mode";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		if (sizeof($parameters) == 0 || (sizeof($parameters) == 1 && $parameters[0] == "")) {
			if (Util::isMaintenanceMode()) irc_out("|r|Site is in maintanence mode: |g|" . Util::getMaintenanceReason());
			else irc_out("|g|Site is not in maintenance mode");
		} else if ($parameters[0] == "off") {
			Db::execute("delete from zz_storage where locker in ('maintenance', 'MaintenanceReason')");
		} else if ($parameters[0] == "on") {
			array_shift($parameters);
			$reason = implode(" ", $parameters);
			if ($reason == "") $reason = "No reason given for this maintenance mode";
			Db::execute("replace into zz_storage values ('maintenance', 'true')");
			Db::execute("replace into zz_storage values ('MaintenanceReason', :reason)", array(":reason" => $reason));
			irc_out("|r|Maintenance mode engaged!  Reason: |g|$reason");
		} else irc_error("Invalid parameters specified, you suck!");
	}
    public function isHidden() { return false; }
}
