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

	$teamAFilter = buildFilter($teamA, "ta");
	$teamBFilter = buildFilter($teamB, "tb");

	$teamALosses = getLosses($teamAFilter, $teamBFilter, $startTime, $endTime, "ta");
	$teamBLosses = getLosses($teamAFilter, $teamBFilter, $startTime, $endTime, "tb");
	$stats = createSummaryArray($teamALosses, $teamBLosses);

	$kills = getLast50($teamAFilter, $teamBFilter, $startTime, $endTime);

	$data["stats"] = $stats["stats"];
	$data["teamA"] = array("name" => $teamAName, "stats" => $stats["teamA"]);
	$data["teamB"] = array("name" => $teamBName, "stats" => $stats["teamB"]);
	$data["kills"] = $kills;
	$data["campaign"] = $campaign;
}
$app->render("campaign.html", array("data" => $data, "error" => $error));

function buildFilter($team, $prefix) {
	$arr = array();
	foreach($team as $id => $entities) {
		$arr[] = "$prefix.$id in (" . implode(", ", $entities) . ")";
	}
	return "(" . implode(" AND ", $arr) . ")";
}

function getLosses($filterA, $filterB, $startTime, $endTime, $prefix) {
	$end = $endTime == null ? "" : " and ta.dttm <= '$endTime' and tb.dttm <= '$endTime' ";
	return Db::query("select groupID, sum(1) kills, sum(total_price) total, sum(points) points from (select $prefix.groupID, $prefix.total_price, $prefix.points from zz_participants ta left join zz_participants tb on (ta.killID = tb.killID) where ta.dttm >= '$startTime' and tb.dttm >= '$startTime' $end and $filterA and $filterB and ta.killID = tb.killID and $prefix.isVictim = 1 group by $prefix.killID) as foo group by groupID");
}

function getLast50($filterA, $filterB, $startTime, $endTime) {
	$end = $endTime == null ? "" : " and ta.dttm <= '$endTime' and tb.dttm <= '$endTime' ";
	$result = Db::query("select distinct ta.killID from zz_participants ta left join zz_participants tb on (ta.killID = tb.killID) where ta.dttm >= '$startTime' and tb.dttm >= '$startTime' $end and $filterA and $filterB and ta.killID = tb.killID order by ta.dttm desc limit 50");
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
