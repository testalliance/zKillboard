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

class cli_killwriter implements cliCommand
{
	public function getDescription()
	{
		return "";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

        public function getCronInfo()
        {
                return array(600 => "");
        }

	public function execute($parameters, $db)
	{
		global $fsKillmails;
		if ($fsKillmails !== true) return;

		$start = Db::queryField("select min(killID) min from zz_killid where writ = 0", "min", array(), 0);
		$start = floor($start / 1000);
		$end = Db::queryField("select max(killID) min from zz_killid where writ = 0", "min", array(), 0);
		$end = floor($end / 1000);

		$totalKillsWritten = 0;

		for($i = $start; $i <= $end; $i++)
		{
			$min = $i >= 0 ? $i * 1000 : ($i * 1000) + 1;
			$max = $min + 999;
			$kills = Db::query("select killID from zz_killid where killID >= $min and killID <= $max and writ = 0");

			if (count($kills) == 0) continue;
			$totalKillsWritten += count($kills);
	
			$killIDs = array();
			foreach($kills as $kill)
			{
				$killIDs[] = $kill["killID"];
			}
			$set = Db::query("select killID, kill_json json from zz_killmails where killID in (" . implode(",", $killIDs) . ")");

			Killmail::massSet($set);
		}

		Log::log("KillWriter wrote $totalKillsWritten kills");
	}
}
