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

class cli_dna implements cliCommand
{
	public function getDescription()
	{
		return "Runs statistics against processed kills in the queue.";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => ""); // Always run
	}

	public function execute($parameters, $db)
	{
		Db::execute("delete from zz_dna where killDate < date_sub(now(), interval 31 day)");
		Db::execute("delete from zz_dna where total <= 5 and killDate < date_sub(now(), interval 14 day)");
		$calcCount = 0;
		$timer = new Timer();
		while ($timer->stop() < 65000) {
			$processedKills = Db::query("select killID from zz_dna_queue limit 100", array(), 0);
			if (count($processedKills) == 0) {
				sleep(5);
				continue;
			}

			$ignoreGroups = array(29, 237, 601);
			foreach($processedKills as $row) {
				$killID = $row["killID"];
				Db::execute("delete from zz_dna_queue where killID = :killID", array(":killID" => $killID));
				if ($killID < 0) continue;

				// Add points and total value to the json stored in the database
				$raw = Killmail::get($killID);
				$kill = json_decode($raw, true);
				Info::addInfo($kill);

				$groupID = $kill["victim"]["groupID"];
				if (in_array($groupID, $ignoreGroups)) continue;

				$killID = $kill["killID"];
				$shipTypeID = $kill["victim"]["shipTypeID"];
				$items = $kill["items"];
				$dttm = $kill["killTime"];

				if (sizeof($items) == 0) continue;

				$fit = array();
				foreach($items as $item) {
					if ($item["fittable"] != 1) continue;
					$qty = $item["qtyDropped"] + $item["qtyDestroyed"];
					for ($i = 1; $i <= $qty; $i++) $fit[] = $item["typeID"];
				}
				if (sizeof($fit) <= 5) continue;
				asort($fit);
				$dna = implode(":", $fit);
				Db::execute("insert into zz_dna (shipTypeID, dna, killID, killDate, total) values (:typeID, :dna, :killID, date(:dttm), 1)
						on duplicate key update total = total + 1",
					array(":typeID" => $shipTypeID, ":dna" => $dna, ":killID" => $killID, ":dttm" => $dttm));
				$calcCount++;
			}
		}
		if ($calcCount > 0) Log::log("DNA'ed $calcCount kills");
	}
}
