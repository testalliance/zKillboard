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
 
$parameters = array();
$names = array();

getIDs("characterID", $parameters, UserConfig::get("character"));
getIDs("corporationID", $parameters, UserConfig::get("corporation"));
getIDs("allianceID", $parameters, UserConfig::get("alliance"));
getIDs("factionID", $parameters, UserConfig::get("faction"));
getIDs("shipTypeID", $parameters, UserConfig::get("ship"));
getIDs("solarSystemID", $parameters, UserConfig::get("system"));
getIDs("regionID", $parameters, UserConfig::get("region"));
if(empty($parameters)) throw new Exception("Nothing to track, please add entities to the tracker from your account page.");

GetNames("character", $names, UserConfig::get("character"));
GetNames("corporation", $names, UserConfig::get("corporation"));
GetNames("alliance", $names, UserConfig::get("alliance"));
GetNames("faction", $names, UserConfig::get("faction"));
GetNames("ship", $names, UserConfig::get("ship"));
GetNames("systems", $names, UserConfig::get("system"));
GetNames("regions", $names, UserConfig::get("region"));

$parameters["combined"] = true;
$limit = 100;
$parameters["limit"] = $limit;

$pageTitle = "Tracking";

$kills = Kills::getKills($parameters);

// Flag losses as red
unset($parameters["limit"]);
unset($parameters["combined"]);
foreach($parameters as $columnName=>$ids) {
	foreach($ids as $id) {
		$kills = Kills::mergeKillArrays($kills, array(), $limit, $columnName, $id);
	}
}
$app->render("tracker.html", array("kills" => $kills, "pageTitle" => $pageTitle, "tracking" => $names));

function getIDs($filterName, &$ids, $array) {
    if (is_null($array) || sizeof($array) == 0) return;
    $filter = array();
    foreach ($array as $row) {
        $filter[] = $row["id"];
    }
    $ids[$filterName] = $filter;
}

function getNames($filterName, &$names, $array) {
    if (is_null($array) || sizeof($array) == 0) return;
    $filter = array();
    foreach ($array as $row) {
        $filter[] = $row["name"];
    }
    $names[$filterName] = $filter;
}
