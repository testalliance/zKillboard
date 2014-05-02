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

class cli_summary implements cliCommand
{
	public function getDescription()
	{
		return "Executes once an hour and outputs a summary of imported kills";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
	{
                $minute = date("i");
                if ($minute != "00") return;
	
		$lastActualKills = $db->queryField("select contents count from zz_storage where locker = 'actualKills'", "count", array(), 0);
		$actualKills = $db->queryField("select count(*) count from zz_killmails where processed != 0", "count", array(), 0);

		$lastTotalKills = $db->queryField("select contents count from zz_storage where locker = 'totalKills'", "count", array(), 0);
		$totalKills = $db->queryField("select count(*) count from zz_killmails", "count", array(), 0);

		$db->execute("replace into zz_storage (locker, contents) values ('totalKills', $totalKills)");
		$db->execute("replace into zz_storage (locker, contents) values ('actualKills', $actualKills)");
		$db->execute("delete from zz_storage where locker like '%KillsProcessed'");

		$actualDifference = number_format($actualKills - $lastActualKills, 0);
		$totalDifference = number_format($totalKills - $lastTotalKills, 0);

		Log::irc("|g|$actualDifference|n| mails processed | |g|$totalDifference|n| kills added");
	}
}
