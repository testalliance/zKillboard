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

class Account
{
	public static function getUserTrackerData()
	{
		$entities = array("character", "corporation", "alliance", "faction", "ship", "item", "system", "region");
		$entitylist = array();
		
		foreach($entities as $ent)
		{
			$result = UserConfig::get($ent);
			$part = array();
			
			if($result != null) foreach($result as $row) {
				switch($ent)
				{
					case "system":
						$row["solarSystemID"] = $row["id"];
						$row["solarSystemName"] = $row["name"];
						$sunType = Db::queryField("SELECT sunTypeID FROM ccp_systems WHERE solarSystemID = :id", "sunTypeID", array(":id" => $row["id"]));
						$row["sunTypeID"] = $sunType;
					break;

					case "item":
						$row["typeID"] = $row["id"];
						$row["shipName"] = $row["name"];

					case "ship":
						$row["shipTypeID"] = $row["id"];
						$row["${ent}Name"] = $row["name"];
					break;
					
					default:
						$row["${ent}ID"] = $row["id"];
						$row["${ent}Name"] = $row["name"];
					break;
				}
				$part[] = $row;
			}
			$entlist[$ent] = $part;
		}
		return $entlist;
	}
}
