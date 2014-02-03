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

class cli_crestapi implements cliCommand
{
	public function getDescription()
	{
		return "";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		global $baseDir;
		@mkdir("{$baseDir}cache/crest/");

		$crests = Db::query("select * from zz_crest_killmail where processed = 0 order by killID", array(), 0);
		foreach ($crests as $crest) {
			try {
				$killID = $crest["killID"];
				$hash = $crest["hash"];
				$cacheFile = "{$baseDir}cache/crest/$killID.json";
				if (!file_exists($cacheFile)) {
					$url = "http://public-crest.eveonline.com/killmails/$killID/$hash/";
					$contents = @file_get_contents($url);
					file_put_contents($cacheFile, $contents);
				}
				$perrymail = new \Perry\Representation\Eve\v1\Killmail(file_get_contents($cacheFile));

				$killmail = array();
				$killmail["killID"] = (int) $killID;
				$killmail["solarSystemID"] = (int) $perrymail->solarSystem->id;
				$killmail["killTime"] = str_replace(".", "-", $perrymail->killTime);
				$killmail["moonID"] = (int) @$perrymail->moon->id;

				$victim = array();
				$victim["shipTypeID"] = (int) $perrymail->victim->shipType->id;
				$victim["characterID"] = (int) @$perrymail->victim->character->id;
				$victim["characterName"] = (string) @$perrymail->victim->character->name;
				$victim["corporationID"] = (int) $perrymail->victim->corporation->id;
				$victim["corporationName"] = (string) @$perrymail->victim->corporation->name;
				$victim["allianceID"] = (int) @$perrymail->victim->alliance->id;
				$victim["allianceName"] = (string) @$perrymail->victim->alliance->name;
				$victim["factionID"] = (int) @$perrymail->victim->faction->id;
				$victim["factionName"] = (string) @$perrymail->victim->faction->name;
				$victim["damageTaken"] = (int) @$perrymail->victim->damageTaken;

				$killmail["victim"] = $victim;

				$attackers = array();
				foreach($perrymail->attackers as $attacker) {
					$aggressor = array();
					$aggressor["characterID"] = (int) @$attacker->character->id;
					$aggressor["characterName"] = (string) @$attacker->character->name;
					$aggressor["corporationID"] = (int) @$attacker->corporation->id;
					$aggressor["corporationName"] = (string) @$attacker->corporation->name;
					$aggressor["allianceID"] = (int) @$attacker->alliance->id;
					$aggressor["allianceName"] = (string) @$attacker->alliance->name;
					$aggressor["factionID"] = (int) @$attacker->faction->id;
					$aggressor["factionName"] = (string) @$attacker->faction->name;
					$aggressor["securityStatus"] = $attacker->securityStatus;
					$aggressor["damageDone"] = (int) @$attacker->damageDone;
					$aggressor["finalBlow"] = (int) @$attacker->finalBlow;
					$aggressor["weaponTypeID"] = (int) @$attacker->shipType->id;
					$aggressor["shipTypeID"] = (int) @$attacker->shipType->id;
					$attackers[] = $aggressor;
				}
				$killmail["attackers"] = $attackers;

				$items = array();
				foreach($perrymail->victim->items as $item) {
					$i = array();
					$i["typeID"] = (int) @$item->itemType->id;
					$i["flag"] = (int) @$item->flag;
					$i["qtyDropped"] = (int) @$item->quantityDropped;
					$i["qtyDestroyed"] = (int) @$item->quantityDestroyed;
					$i["singleton"] = (int) @$item->singleton;
					$items[] = $i;
				}
				$killmail["items"] = $items;

				$json = json_encode($killmail);
				$killmailHash = Util::getKillHash(null, json_decode($json));
				Db::execute("replace into zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)", array(":killID" => $killID, ":hash" => $hash, ":source" => "crest:$killID", ":json" => $json));
			} catch (Exception $ex) {
				/*echo "$killID\n";
				print_r($ex);
				die();*/
			}
		}
	}

	public static function convertToArray($obj)
	{
		if (is_object($obj)) $obj = (array)$obj;
		if (is_array($obj)) {
			$new = array();
			foreach ($obj as $key => $val) {
				if (md5($key) == "afeaf5f8dd5f04379ea3a6158f4ecae1" || md5($key) == "a58455001d157d434f1226aadb07bdc5") continue;
				if ($key == "*genericMembers" || md5($key) == "fbe2e69c614c0ef9bfa2b85e64932253") {
					$values = self::convertToArray($val);
					foreach ($values as $vkey => $vvalue) {
						$new[$vkey] = $vvalue;
					}
				}
				else $new[$key] = self::convertToArray($val);
			}
		} else {
			$new = $obj;
		}

		return $new;
	}

}
