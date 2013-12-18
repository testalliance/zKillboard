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

class irc_deletekill implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$killID = (int) $parameters[0];
		if ($killID >= 0) irc_error("|r|Can only delete manually posted killmails with a killID < 0");
		// Verify the kill exists
		$count = Db::execute("select count(*) count from zz_killmails where killID = :killID", array(":killID" => $killID));
		if ($count == 0) irc_error("|r|killID $killID not found.");
		// Remove it from the stats
		Stats::calcStats($killID, false);
		// Remove it from the kill tables
		Db::execute("delete from zz_participants where killID = :killID", array(":killID" => $killID));
		// Mark the kill as deleted
		Db::execute("update zz_killmails set processed = 2 where killID = :killID", array(":killID" => $killID));
		irc_out("killID |g|$killID|n| has been deleted.");
	}
    public function isHidden() { return false; }
}
