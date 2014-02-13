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

$data = array();
$error = "";
$campaign = Db::queryRow("SELECT * FROM zz_campaigns WHERE name = :name", array(":name" => $name), 0);
if (!isset($campaign)) $error = "No such campaign...";
else {
	$definition = isset($campaign["definition"]) ? json_decode($campaign["definition"]) : array();

	$startTime = $campaign["start_dttm"];
	$endTime = $campaign["end_dttm"];

	// TODO pull from definition
	$teamA = array("corporationID" => array(1699307293));
	$teamB = array("corporationID" => array(1741770561));
	$teamAName = "Red Federation";
	$teamBName = "Blue Republic";

	$teamAFilter = buildFilter($teamA, "ta.", $startTime, $endTime);
	$teamBFilter = buildFilter($teamB, "tb.", $startTime, $endTime);

	$teamALosses = getLosses($teamAFilter, $teamBFilter, "ta");
	$teamBLosses = getLosses($teamAFilter, $teamBFilter, "tb");
	$stats = createSummaryArray($teamALosses, $teamBLosses);

	$data["top"] = array();
	$data["top"][] = Info::doMakeCommon("Top Pilots", "characterID", getTop("characterID", $teamAFilter, $teamBFilter, "ta."));
	$data["top"][] = Info::doMakeCommon("Top Corporations", "corporationID", getTop("corporationID", $teamAFilter, $teamBFilter, "ta."));
	$data["top"][] = Info::doMakeCommon("Top Alliances", "allianceID", getTop("allianceID", $teamAFilter, $teamBFilter, "ta."));
	$data["top"][] = Info::doMakeCommon("Top Systems", "solarSystemID", getTop("solarSystemID", $teamAFilter, $teamBFilter, "ta."));
	$data["top"][] = Info::doMakeCommon("Top Ships", "shipTypeID", getTop("shipTypeID", $teamAFilter, $teamBFilter, "ta."));
	$data["top"][] = Info::doMakeCommon("Top Weapons", "weaponTypeID", getTop("weaponTypeID", $teamAFilter, $teamBFilter, "ta."));

	$kills = getLast50($teamAFilter, $teamBFilter, $startTime, $endTime);

	$data["stats"] = $stats["stats"];
	$data["teamA"] = array("name" => $teamAName, "stats" => $stats["teamA"]);
	$data["teamB"] = array("name" => $teamBName, "stats" => $stats["teamB"]);
	$data["kills"] = $kills;
	$data["entitiesA"] = getEntities($teamA);
	$data["entitiesB"] = getEntities($teamB);
	$data["campaign"] = $campaign;
}
$app->render("campaign.html", array("data" => $data, "error" => $error));

function buildFilter($team, $prefix, $startTime, $endTime) {
	$arr = array();
	foreach($team as $id => $entities) {
		$arr[] = "{$prefix}$id in (" . implode(", ", $entities) . ")";
	}
	$end = $endTime == null ? "" : " and {$prefix}dttm <= '$endTime'";
	return "((" . implode(" OR ", $arr) . ") and {$prefix}dttm >= '$startTime' $end)";
}

function getLosses($filterB, $filterA, $prefix) {
	return Db::query("select groupID, sum(1) kills, sum(total_price) total, sum(points) points from (select $prefix.groupID, $prefix.total_price, $prefix.points from zz_participants ta left join zz_participants tb on (ta.killID = tb.killID) where $filterA and $filterB and ta.killID = tb.killID and $prefix.isVictim = 1 group by $prefix.killID) as foo group by groupID");
}

function getLast50($filterA, $filterB) {
	$result = Db::query("select distinct ta.killID from zz_participants ta left join zz_participants tb on (ta.killID = tb.killID) where $filterA and $filterB and ta.killID = tb.killID order by ta.dttm desc limit 50");
	return Kills::getKillsDetails($result);
}

function createSummaryArray($teamA, $teamB) {
	$teamAStats = array("destroyed" => 0, "iskDestroyed" => 0, "pointsDestroyed" => 0);
	$teamBStats = array("destroyed" => 0, "iskDestroyed" => 0, "pointsDestroyed" => 0);
	$arr = array();

	foreach($teamA as $group) {
		$groupID = $group["groupID"];
		$groupName = Info::getGroupName($groupID);
		$groupArray = array();
		$groupArray["groupID"] = $groupID;
		$groupArray["destroyed"] = $group["kills"];
		$groupArray["iskDestroyed"] = $group["total"];
		$groupArray["pointsDestroyed"] = $group["points"];
		$groupArray["groupName"] = $groupName;
		$arr[$groupName] = $groupArray;

		$teamAStats["destroyed"] += $group["kills"];
		$teamAStats["iskDestroyed"] += $group["total"];
		$teamAStats["pointsDestroyed"] += $group["points"];
	}
	foreach($teamB as $group) {
		$groupID = $group["groupID"];
		$groupName = Info::getGroupName($groupID);
		$groupArray = isset($arr[$groupName]) ? $arr[$groupName] : array();
		$groupArray["groupID"] = $groupID;
		$groupArray["lost"] = $group["kills"];
		$groupArray["iskLost"] = $group["total"];
		$groupArray["pointsLost"] = $group["points"];
		$groupArray["groupName"] = $groupName;
		$arr[$groupName] = $groupArray;

		$teamBStats["destroyed"] += $group["kills"];
		$teamBStats["iskDestroyed"] += $group["total"];
		$teamBStats["pointsDestroyed"] += $group["points"];
	}
	ksort($arr);

	$cnt = 0;
	$cnid = 0;
	$stats = array();
	$totalcount = ceil(count($arr) / 4);
	foreach($arr as $key=>$q)
	{
		if($cnt == $totalcount)
		{
			$cnid++;
			$cnt = 0;
		}
		$stats[$cnid][] = $q;
		$cnt++;
	}

	return array("stats" => $stats, "teamA" => $teamAStats, "teamB" => $teamBStats);
}

function getTop($column, $filterA, $filterB) {
	$prefix = "ta.";
	$otherPrefix = "tb.";
	$resultA = Db::query("select p.{$column}, count(distinct p.killID) kills from zz_participants p left join zz_participants ta on (p.killID = ta.killID) left join zz_participants tb on (ta.killID = tb.killID) where {$otherPrefix}isVictim = 1 and {$prefix}isVictim = 0 and $filterA and $filterB and p.{$column} != 0 and p.isVictim = 0 group by 1 order by 2 desc limit 10");
	$prefix = "tb.";
	$otherPrefix = "ta.";
	$resultB = Db::query("select p.{$column}, count(distinct p.killID) kills from zz_participants p left join zz_participants ta on (p.killID = ta.killID) left join zz_participants tb on (ta.killID = tb.killID) where {$otherPrefix}isVictim = 1 and {$prefix}isVictim = 0 and $filterA and $filterB and p.{$column} != 0 and p.isVictim = 0 group by 1 order by 2 desc limit 10");
	$result = array_merge($resultA, $resultB);
	usort($result, "sortKills");
	// Clean out duplicates
	$seen = array();
	$finalResult = array();
	foreach($result as $row) {
		$key = $row[$column];
		if (!in_array($key, $seen)) $finalResult[] = $row;
		$seen[] = $key;
	}
	$result = array_slice($finalResult, 0, 5);
	return Info::addInfo($result);
}

function sortKills($v1, $v2) {
	return $v1["kills"] < $v2["kills"];
}

function getEntities($team) {
	$entities = array();
	$entities["alliances"] = getSpecificEntity($team, "allianceID");
	$entities["corporations"] = getSpecificEntity($team, "corporationID");
	$entities["characters"] = getSpecificEntity($team, "characterID");
	Info::addInfo($entities);
	return $entities;
}

function getSpecificEntity($team, $id) {
	$array = array();
	if (isset($team[$id])) foreach($team[$id] as $value) $array[] = array($id => $value);
	return $array;
}
