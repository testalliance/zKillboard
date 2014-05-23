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

/**
 * Parser for raw killmails from ingame EVE.
 */

class Subdomains
{
	public static function getSubdomainParameters($serverName)
	{
		global $app;

		$board = str_replace(".zkillboard.com", "", $serverName);
		$board = str_replace("_", " ", $board);
		$board = preg_replace('/^dot\./i', '.', $board);
		$board = preg_replace('/\.dot$/i', '.', $board);
		if ($board == "www") $app->redirect("https://zkillboard.com", 302);
		$numDays = 7;

		$faction = Db::queryRow("select * from zz_factions where ticker = :board", array(":board" => $board), 3600);
		$alli = Db::queryRow("select * from zz_alliances where ticker = :board order by memberCount desc limit 1", array(":board" => $board), 3600);
		if ($alli) {
			$killID = Db::queryField("select killID from zz_participants where allianceID = :alliID and dttm >= date_sub(now(), interval 6 month) limit 1", "killID", array(":alliID" => $alli["allianceID"]), 3600);
			if (!$killID) $alli = null;
		}
		$corp = Db::queryRow("select * from zz_corporations where ticker = :board and memberCount > 0 order by memberCount desc limit 1", array(":board" => $board), 3600);
		if ($corp) {
			$killID = Db::queryField("select killID from zz_participants where corporationID = :corpID and dttm >= date_sub(now(), interval 6 month) limit 1", "killID", array(":corpID" => $corp["corporationID"]), 3600);
			if (!$killID) $corp = null;
		}

		$columnName = null;
		$id = null;
		if ($faction) $p = array("factionID" => $faction["factionID"]);
		else if ($alli) $p = array("allianceID" => $alli["allianceID"]);
		else if ($corp) $p = array("corporationID" => $corp["corporationID"]);
		else $p = array();

		return $p;
	}

}
