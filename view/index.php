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

global $subDomainKey, $subDomainRow;
if ($subDomainRow) {
	include( "view/overview.php" );
	return;
}

$topIsk = Stats::getTopIsk(array("pastSeconds" => (3*86400), "limit" => 5));
$topPods = Stats::getTopIsk(array("shipTypeID" => 670, "pastSeconds" => (3*86400), "limit" => 5));
$topPointList = Stats::getTopPoints("killID", array("losses" => true, "pastSeconds" => (3*86400), "limit" => 5));
$topPoints = Kills::getKillsDetails($topPointList);

$p = array();
$p["limit"] = 5;
$p["pastSeconds"] = 3 * 86400;
$p["kills"] = true;

$top = array();
$top[] = doMakeCommon("Top Characters - Last 3 Days", "characterID", Stats::getTopPilots($p));
$top[] = doMakeCommon("Top Corporations - Last 3 Days", "corporationID", Stats::getTopCorps($p));
$top[] = doMakeCommon("Top Alliances - Last 3 Days", "allianceID", Stats::getTopAllis($p));

$app->etag(md5(serialize($top)));
$app->expires("+5 minutes");

$app->render("index.html", array("topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints, "topKillers" => $top));

function doMakeCommon($title, $field, $array) {
    $retArray = array();
    $retArray["type"] = str_replace("ID", "", $field);
    $retArray["title"] = $title;
    $retArray["values"] = array();
    foreach($array as $row) {
        $data = $row;
        $data["id"] = $row[$field];
        $data["name"] = $row[$retArray["type"] . "Name"];
        $data["kills"] = $row["kills"];
        $retArray["values"][] = $data;
    }
    return $retArray;
}

