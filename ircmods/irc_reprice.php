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

class irc_reprice implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "Recalculates the ISK value and points of a kill. Usage: |g|.z reprice <killID>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$killID = (int) $parameters[0];
		if ($killID == 0) irc_error("|r|Please provide a valid killID.");
		$count = Db::queryField("select count(*) count from zz_participants where killID = :killID", "count", array(":killID" => $killID));
		if ($count == 0) irc_error("|r|KillID $killID does not exist!");

		$total = Price::updatePrice($killID);
		$points = Points::updatePoints($killID);
		irc_out("|g|$killID|n| repriced to|g| " . number_format($total, 2) . "|n| ISK and |g|" . number_format($points, 0) . "|n| points");
	}
    public function isHidden() { return false; }
}
