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

class Related
{
	public static function buildSummary(&$kills, $parameters = array(), $key)
	{
		if ($kills == null || !is_array($kills) || sizeof($kills) == 0) return array();

		$key = "related:$key";
		$mc = Cache::get($key);
		//if ($mc) return $mc;

		// Determine which entity got on the most killmails
		$involvedArray = self::findWinners($kills, $typeColumn = "allianceID");

		// Sort the array
		uasort($involvedArray, "Related::involvedArraySort");
		reset($involvedArray);

		// Determine sides based on who shot the most of who
		$teamAKills = array();
		$teamBKills = array();
		self::filterKills($kills, $involvedArray, $teamAKills, $teamBKills);

		$teamAKillIDs = array_keys($teamAKills);
		$teamBKillIDs = array_keys($teamBKills);

		$teamAList = array();
		$teamBList = array();
		self::hashPilots($teamBKills, $teamBList, $teamAList);
		self::hashPilots($teamAKills, $teamAList, $teamBList);

		$teamATotals = self::getStatsKillList($teamAKillIDs);
                $teamATotals["pilotCount"] = sizeof($teamAList);
		$teamBTotals = self::getStatsKillList($teamBKillIDs);
                $teamBTotals["pilotCount"] = sizeof($teamBList);

                uksort($teamAList, "self::shipGroupSort");
                uksort($teamBList, "self::shipGroupSort");

		$killHashes = self::buildKillHashArray($kills);
                $teamAList = self::addInfo($teamAList, $killHashes);
                $teamBList = self::addInfo($teamBList, $killHashes);

		// Summarize data

		$retValue = array(
				"teamA" => array(
					"list" => $teamAList,
					"kills" => $teamAKills,
					"totals" => $teamATotals,
					),
				"teamB" => array(
					"list" => $teamBList,
					"kills" => $teamBKills,
					"totals" => $teamBTotals,
					),
				);

		return $retValue;
	}

	private static function filterKills(&$kills, $involvedArray, &$team1, &$team2, $entity = null, $depth = 1)
	{
		if ($depth > 2) return;
		$i = array_keys($involvedArray);
		if ($entity === null) $entity = array_shift($i);
		if ($entity === 0) return;
		$killIDs = @$involvedArray[$entity];
		if ($killIDs === null) return;

		foreach ($killIDs as $killID)
		{
			$kill = @$kills[$killID];
			if ($kill === null) continue;
			$team2[$killID] = $kill;
			self::filterKills($kills, $involvedArray, $team2, $team1, $kill["victim"]["allianceID"], $depth + 1);
			self::filterKills($kills, $involvedArray, $team2, $team1, $kill["victim"]["corporationID"], $depth + 1);
			self::filterKills($kills, $involvedArray, $team2, $team1, $kill["victim"]["characterID"], $depth + 1);
		}
	}

	/**
	 * @param string $typeColumn
	 */
	private static function findWinners($kills, $typeColumn)
	{
		$involvedArray = array();
		foreach ($kills as $killID=>$kill) {
			$finalBlow = $kill["finalBlow"];
			$added = self::addInvolvedEntity($involvedArray, $killID, $finalBlow["allianceID"]);
			if (!$added) $added = self::addInvolvedEntity($involvedArray, $killID, $finalBlow["corporationID"]);
			if (!$added) $added = self::addInvolvedEntity($involvedArray, $killID, $finalBlow["characterID"]);
		}
		return $involvedArray;
	}

	private static function addInvolvedEntity(&$involvedArray, &$killID, &$entity)
	{
		if ($entity == 0) return false;
		if (!isset($involvedArray["$entity"])) $involvedArray["$entity"] = array();
		if (!in_array($killID, $involvedArray["$entity"]))
		{
			$involvedArray["$entity"][] = $killID;
			return true;
		}
		return false;
	}

	public static function involvedArraySort($a, $b)
	{
		return sizeof($a) < sizeof($b);
	}

	/**
	 * @param array $kills
	 * @return array
	 */
	private static function getStatsKillList(&$killIDs)
	{
		$totalPrice = 0;
		$totalPoints = 0;
		$groupIDs = array();
		$totalShips = 0;
		foreach ($killIDs as $killID) {
			$kill = Kills::getKillDetails($killID);
			$info = $kill["info"];
			$victim = $kill["victim"];
			$totalPrice += $info["total_price"];
			$totalPoints += $info["points"];
			$groupID = $victim["groupID"];
			if (!isset($groupIDs[$groupID])) {
				$groupIDs[$groupID] = array();
				$groupIDs[$groupID]["count"] = 0;
				$groupIDs[$groupID]["isk"] = 0;
				$groupIDs[$groupID]["points"] = 0;
			}
			$groupIDs[$groupID]["groupID"] = $groupID;
			$groupIDs[$groupID]["count"]++;
			$groupIDs[$groupID]["isk"] += $info["total_price"];
			$groupIDs[$groupID]["points"] += $info["points"];
			$totalShips++;
		}
		Info::addInfo($groupIDs);
		return array(
				"total_price" => $totalPrice, "groupIDs" => $groupIDs, "totalShips" => $totalShips,
				"total_points" => $totalPoints
			    );
	}

	protected static function hashPilots($kills, &$teamBs, &$teamAs)
	{
		$pilotsAdded = array();
		ksort($kills);

		$validEntities = array();
		$validEntities["allis"] = array();
		$validEntities["corps"] = array();
		// Preprocess the opposition
		foreach ($kills as $killID => $kill) {
			$victim = $kill['victim'];

			if ($victim["allianceID"] != 0) $validEntities["allis"][] = $victim["allianceID"];
			if ($victim["corporationID"] != 0) $validEntities["corps"][] = $victim["corporationID"];
		}

		foreach ($kills as $killID => $kill) {
			$victim = $kill['victim'];
			$characterID = $victim["characterID"];
			$shipTypeID = $victim["shipTypeID"];
			if (($shipTypeID == 0 || $shipTypeID == 670) && in_array($characterID, $pilotsAdded)) continue;
			if ($shipTypeID != 670 && $shipTypeID != 0) $pilotsAdded[] = $characterID;
			self::addRelatedPilots($teamBs, $kill, $victim);

			$attackers = Db::query("select * from zz_participants where killID = :killID and isVictim = '0'",
					array(":killID" => $killID), 3600);
			foreach ($attackers as $attacker) {
				$shipTypeID = $attacker["shipTypeID"];

				if ($attacker["allianceID"] != 0 && in_array($attacker["allianceID"], $validEntities["allis"])) continue;
				if (in_array($attacker["corporationID"], $validEntities["corps"])) continue;

				if ($shipTypeID != 0 && $shipTypeID != 670) self::addRelatedPilots($teamAs, $kill, $attacker);
				if (!in_array($attacker["characterID"], $pilotsAdded)) $pilotsAdded[] = $attacker["characterID"];
			}
			foreach ($attackers as $attacker) {
				if (!in_array($attacker["characterID"], $pilotsAdded)) self::addRelatedPilots($teamAs, $kill, $attacker);
			}
		}
	}

	/**
	 * @param array $array
	 * @param array $kill
	 * @param array $pilot
	 */
	protected static function addRelatedPilots(&$array, &$kill, &$pilot)
	{
		$characterID = $pilot['characterID'];
		$corporationID = $pilot['corporationID'];
		$allianceID = $pilot["allianceID"];
		$shipTypeID = $pilot['shipTypeID'];

		if ($characterID == 0) return;

		if ($shipTypeID != 670 && $shipTypeID != 0) {
			$podHash = "670|$corporationID|$characterID|$allianceID";
			$s0Hash = "0|$corporationID|$characterID|$allianceID";
			unset($array[$podHash]);
			unset($array[$s0Hash]);
		}
		$hash = "$shipTypeID|$corporationID|$characterID|$allianceID";
		$array[$hash] = array("kill" => $kill, "pilot" => $pilot);
	}

	protected static function buildKillHashArray($kills)
	{
		$hashes = array();
		foreach($kills as $kill) {
			$pilot = $kill["victim"];
			$characterID = $pilot['characterID'];
			$corporationID = $pilot['corporationID'];
			$allianceID = $pilot["allianceID"];
			$shipTypeID = $pilot['shipTypeID'];
			$hash = "$shipTypeID|$corporationID|$characterID|$allianceID";
			$hashes[$hash] = $pilot["killID"];
		}
		return $hashes;
	}

	/**
	 * @param array $array
	 * @param array $killHash
	 * @return array
	 */
	private static function addInfo($array, $killHash)
	{
		$results = array();
		foreach ($array as $hash => $kill) {
			$split = explode("|", $hash);
			$row = array();
			$row["shipTypeID"] = $split[0];
			$row["corporationID"] = $split[1];
			$row["characterID"] = $split[2];
			$row["allianceID"] = $split[3];
			$row["destroyed"] = in_array($hash, array_keys($killHash)) ? $killHash[$hash] : 0;
			$podHash = "670|" . $row["corporationID"] . "|" . $row["characterID"] . "|" . $row["allianceID"];
			$row["podded"] = in_array($podHash, $killHash) ? 1 : 0;
			Info::addInfo($row);
			$results[] = $row;
		}
		return $results;
	}

	/**
	 * @param array $a
	 * @param array $b
	 * @return int|null|string
	 */
	public static function shipGroupSort($a, $b)
	{
		$split1 = explode("|", $a);
		$split2 = explode("|", $b);
		$shipTypeID1 = $split1[0];
		$shipTypeID2 = $split2[0];

		$volumeID1 = Db::queryField("select volume from ccp_invTypes where typeID = :typeID", "volume",
				array(":typeID" => $shipTypeID1));
		$volumeID2 = Db::queryField("select volume from ccp_invTypes where typeID = :typeID", "volume",
				array(":typeID" => $shipTypeID2));

		if ($volumeID1 != $volumeID2) return $volumeID2 - $volumeID1;

		if ($shipTypeID1 == $shipTypeID2) {
			if ($split1[1] == $split2[1]) {
				return $split1[2] - $split2[2];
			}
			return $split1[1] - $split2[1];
		}

		$groupID1 = Db::queryField("select groupID from ccp_invTypes where typeID = :typeID", "groupID",
				array(":typeID" => $shipTypeID1));
		$groupID2 = Db::queryField("select groupID from ccp_invTypes where typeID = :typeID", "groupID",
				array(":typeID" => $shipTypeID2));
		if ($groupID1 == $groupID2) {
			return $shipTypeID2 - $shipTypeID1;
		}
		if ($groupID1 == $groupID2) return 0;
		return $groupID2 - $groupID1;
	}
}
