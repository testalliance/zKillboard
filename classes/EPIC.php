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

class EPIC
{
	public static function getArray($id)
	{
		$jsonRaw = Db::queryField("SELECT kill_json FROM zz_killmails WHERE killID = :killID", "kill_json", array(":killID" => $id));
		$decode = json_decode($jsonRaw, true);
		$killarray = Info::addInfo($decode);
		return $killarray;
	}

	public static function getJson($id)
	{
		$jsonRaw = Db::queryField("SELECT kill_json FROM zz_killmails WHERE killID = :killID", "kill_json", array(":killID" => $id));
		$killarray = Info::addInfo(json_decode($jsonRaw, true));
		return json_encode($killarray);
	}

	public static function getMail($id)
	{
		$Cache = Cache::get($id);
		//if($Cache) return $Cache;

		$k = self::getArray($id);
		$kill = Kills::getKillDetails($id);

		$mail = $k["killTime"] . "\n";
		$mail .= "\n";
		$mail .= "Victim: " . $k["victim"]["characterName"] . "\n";
		$mail .= "Corp: " . $k["victim"]["corporationName"] . "\n";
		if (!isset($k["victim"]["allianceName"]) || $k["victim"]["allianceName"] == "")
			$k["victim"]["allianceName"] = "None";
		$mail .= "Alliance: " . $k["victim"]["allianceName"] . "\n";
		if (!isset($k["victim"]["factionName"]) || $k["victim"]["factionName"] == "")
			$k["victim"]["factionName"] = "None";
		$mail .= "Faction: " . $k["victim"]["factionName"] . "\n";
		if (!isset($k["victim"]["shipName"]) || $k["victim"]["shipName"] == "")
			$k["victim"]["shipName"] = "None";
		$mail .= "Destroyed: " . $k["victim"]["shipName"] . "\n";
		if (!isset($k["solarSystemName"]) || $k["solarSystemName"] == "")
			$k["solarSystemName"] = "None";
		$mail .= "System: " . $k["solarSystemName"] . "\n";
		if (!isset($k["solarSystemSecurity"]) || $k["solarSystemSecurity"] == "")
			$k["solarSystemSecurity"] = (int) 0;
		$mail .= "Security: " . $k["solarSystemSecurity"] . "\n";
		if (!isset($k["victim"]["damageTaken"]) || $k["victim"]["damageTaken"] == "")
			$k["victim"]["damageTaken"] = (int) 0;
		$mail .= "Damage Taken: " . $k["victim"]["damageTaken"] . "\n\n";
		if(isset($k["attackers"]))
		{
			$mail .= "Involved parties:\n\n";
			foreach ($k["attackers"] as $inv)
			{
				if ($inv["finalBlow"] == 1)
					$mail .= "Name: " . $inv["characterName"] . " (laid the final blow)\n";
				else if (strlen($inv["characterName"]))
					$mail .= "Name: " . $inv["characterName"] . "\n";
				if (strlen($inv["characterName"])) $mail .= "Security: " . $inv["securityStatus"] . "\n";
				$mail .= "Corp: " . $inv["corporationName"] . "\n";
				if (!isset($inv["allianceName"]) || $inv["allianceName"] == "")
					$inv["allianceName"] = "None";
				$mail .= "Alliance: " . $inv["allianceName"] . "\n";
				if (!isset($inv["factionName"]) || $inv["factionName"] == "")
					$inv["factionName"] = "None";
				$mail .= "Faction: " . $inv["factionName"] . "\n";
				if (!isset($inv["shipName"]) || $inv["shipName"] == "")
					$inv["shipName"] = "None";
				$mail .= "Ship: " . $inv["shipName"] . "\n";
				if (!isset($inv["weaponName"]) || $inv["weaponName"] == "")
					$inv["weaponName"] = $inv["shipName"];
				$mail .= "Weapon: " . $inv["weaponName"] . "\n";
				$mail .= "Damage Done: " . $inv["damageDone"] . "\n\n";
			}
		}
		$mail .= "\n";
		$dropped = array();
		$destroyed = array();
		if (isset($k["items"]))
		{
			foreach ($k["items"] as $itm)
			{
				if ($itm["qtyDropped"] > 0) {
					$asdf = "";
					if ($itm["qtyDropped"] > 1)
						$asdf = $itm["typeName"] . ", Qty: " . $itm["qtyDropped"];
					else
						$asdf = $itm["typeName"];
					if (isset($itm["flagName"])) {
						if ($itm["flagName"] == "Cargo")
							$asdf = $asdf . " (Cargo)";
						elseif ($itm["flagName"] == "Drone Bay")
							$asdf = $asdf . " (Drone Bay)";
						elseif ($itm["singleton"] == 2)
							$asdf = $asdf . " (Copy)";
					}
					$dropped[] = $asdf;
				}
				if ($itm["qtyDestroyed"] > 0) {
					$asdf = "";
					if ($itm["qtyDestroyed"] > 1)
						$asdf = $itm["typeName"] . ", Qty: " . $itm["qtyDestroyed"];
					else
						$asdf = $itm["typeName"];
					if (isset($itm["flagName"])) {
						if ($itm["flagName"] == "Cargo")
							$asdf = $asdf . " (Cargo)";
						elseif ($itm["flagName"] == "Drone Bay")
							$asdf = $asdf . " (Drone Bay)";
						elseif ($itm["singleton"] == 2)
							$asdf = $asdf . " (Copy)";
					}
					$destroyed[] = $asdf;
				}
			}
		}
		if ($destroyed) {
			$mail .= "Destroyed items:\n\n";
			foreach ($destroyed as $items)
				$mail .= $items . "\n";
		}
		$mail .= "\n";
		if ($dropped) {
			$mail .= "Dropped items:\n\n";
			foreach ($dropped as $items)
				$mail .= $items . "\n";
		}
		Cache::set($id, $mail, 604800);
		return $mail;
	}
}
