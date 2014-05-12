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

$pageTitle = "";
$serverName = $_SERVER["SERVER_NAME"];
global $baseAddr;
if ($serverName != $baseAddr) {
	$board = str_replace(".zkillboard.com", "", $serverName);
	$board = str_replace("_", " ", $board);
	$board = preg_replace('/^dot\./i', '.', $board);
	$board = preg_replace('/\.dot$/i', '.', $board);
	$numDays = 7;

	$faction = Db::queryRow("select * from zz_factions where ticker = :board", array(":board" => $board), 3600);
	$alli = Db::queryRow("select * from zz_alliances where ticker = :board order by memberCount desc limit 1", array(":board" => $board), 3600);
	if ($alli) {
		$killID = Db::queryField("select killID from zz_participants where allianceID = :alliID and dttm >= date_sub(now(), interval 6 month) limit 1", "killID", array(":alliID" => $alli["allianceID"]), 3600);
		if (!$killID) $alli = null;
	}
	$corp = Db::queryRow("select * from zz_corporations where ticker = :board and memberCount > 0 order by memberCount desc limit 1", array(":board" => $board), 3600);
	if ($corp) {
		$killID = Db::queryField("select killID from zz_participants where corporationID = :corpID and dttm >= date_sub(now(), interval 6 month) limit 1", "killID", array(":corpID" => $corp["corporationID"]), 3600);
		if (!$killID) $corp = null;
	}

	$columnName = null;
	$id = null;
	if ($faction) $p = array("factionID" => $faction["factionID"]);
	else if ($alli) $p = array("allianceID" => $alli["allianceID"]);
	else if ($corp) $p = array("corporationID" => $corp["corporationID"]);
	else $p = array();

	$columnName = key($p);
	$id = reset($p);

	if (sizeof($p) < 1) die($board . " ticker not found or entity has not had a kill in the last 6 months...");

	$topPoints = array();
	$topPods = array();

	$p["kills"] = true;
	$p["pastSeconds"] = ($numDays*86400);

	$top = array();
	$top[] = Info::doMakeCommon("Top Characters", "characterID", Stats::getTopPilots($p));
	$top[] = ($columnName != "corporationID" ? Info::doMakeCommon("Top Corporations", "corporationID", Stats::getTopCorps($p)) : array());
	$top[] = ($columnName != "corporationID" && $columnName != "allianceID" ? Info::doMakeCommon("Top Alliances", "allianceID", Stats::getTopAllis($p)) : array());
	$top[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p));
	$top[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topIsk = Stats::getTopIsk($p);
	unset($p["pastSeconds"]);
	unset($p["kills"]);

	// get latest kills
	$killsLimit = 50;
	$p["limit"] = $killsLimit;
	$kills = Kills::getKills($p);

	$kills = Kills::mergeKillArrays($kills, array(), $killsLimit, $columnName, $id);

	Info::addInfo($p);
	$pageTitle = array();
	foreach($p as $key=>$value) {
		if (strpos($key, "Name") !== false) $pageTitle[] = $value;
	}
	$pageTitle = implode(",", $pageTitle);
} else {
	$topPoints = array();
	$topIsk = json_decode(Storage::retrieve("TopIsk"), true);
	$topPods = json_decode(Storage::retrieve("TopPods"), true);
	$topPointList = json_decode(Storage::retrieve("TopPoints"), true);

	if(is_array($topPointList)) $topPoints = Kills::getKillsDetails($topPointList);

	$top = array();
	$top[] = json_decode(Storage::retrieve("TopChars"), true);
	$top[] = json_decode(Storage::retrieve("TopCorps"), true);
	$top[] = json_decode(Storage::retrieve("TopAllis"), true);
	$top[] = json_decode(Storage::retrieve("TopShips"), true);
	$top[] = json_decode(Storage::retrieve("TopSystems"), true);

	// get latest kills
	$killsLimit = 50;
	$kills = Kills::getKills(array("limit" => $killsLimit));

}
$app->render("index.html", array("topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints, "topKillers" => $top, "kills" => $kills, "page" => 1, "pageType" => "index", "pager" => true, "pageTitle" => $pageTitle));
