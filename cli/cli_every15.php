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

class cli_every15 implements cliCommand
{
	public function getDescription()
	{
		return "Tasks that needs to run every 15 minutes. |g|Usage: qtlyhourly";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(
			900 => ""
		);
	}

	public function execute($parameters, $db)
	{
		$p = array();
		$p["limit"] = 5;
		$p["pastSeconds"] = 3 * 86400;
		$p["kills"] = true;

		Storage::store("Top3dayChars", json_encode(Info::doMakeCommon("Top Characters - Last 3 Days", "characterID", Stats::getTopPilots($p))));
		Storage::store("Top3dayCorps", json_encode(Info::doMakeCommon("Top Corporations - Last 3 Days", "corporationID", Stats::getTopCorps($p))));
		Storage::store("Top3dayAlli", json_encode(Info::doMakeCommon("Top Alliances - Last 3 Days", "allianceID", Stats::getTopAllis($p))));
		Storage::store("TopIsk", json_encode(Stats::getTopIsk(array("pastSeconds" => (3*86400), "limit" => 5))));
		Storage::store("TopPods", json_encode(Stats::getTopIsk(array("groupID" => 29, "pastSeconds" => (3*86400), "limit" => 5))));
		Storage::store("TopPoints", json_encode(Stats::getTopPoints("killID", array("losses" => true, "pastSeconds" => (3*86400), "limit" => 5))));
		Storage::store("KillCount", $db->queryField("select count(*) count from zz_killmails", "count"));
		Storage::store("ActualKillCount", $db->queryField("select count(*) count from zz_killmails where processed = 1", "count"));
	}
}
