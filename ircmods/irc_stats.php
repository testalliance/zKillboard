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

class irc_stats implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Lists the number of kills in the database. Usage: |g|.z stats|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$message = "";

		$unParsed = Db::queryField("select count(*) count from zz_killmails where processed = 0", "count", array(), 0);
		$message .= "|g|Unparsed kills:|n| " . number_format($unParsed) . " / ";

		$totalCount = Storage::retrieve("ActualKillCount");
		$message .= "|g|Total kills:|n| " . number_format($totalCount) . " / ";

		$killCount = Storage::retrieve("KillCount");
		$message .= "|g|Actual Kills:|n| " . number_format($killCount) . " / ";

		$userCount = Db::queryField("select count(*) count from zz_users", "count", array(), 000);
		$message .= "|g|Users:|n| " . number_format($userCount) . " / ";

		$apiCount = Db::queryField("select count(*) count from zz_api where errorCode = 0", "count", array(), 000);
		$message .= "|g|Valid APIs:|n| " . number_format($apiCount);

		irc_out($message);
	}
    public function isHidden() { return false; }
}
