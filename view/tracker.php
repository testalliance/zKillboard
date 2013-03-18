<?php
$ids = array();
$names = array();

getIDs("characterID", $ids, UserConfig::get("character"));
getIDs("corporationID", $ids, UserConfig::get("corporation"));
getIDs("allianceID", $ids, UserConfig::get("alliance"));
getIDs("factionID", $ids, UserConfig::get("faction"));
getIDs("shipTypeID", $ids, UserConfig::get("ship"));
getIDs("solarSystemID", $ids, UserConfig::get("system"));
getIDs("regionID", $ids, UserConfig::get("region"));
if(empty($ids))
    throw new Exception("Nothing to track, please add entities to the tracker from your account page.");

GetNames("character", $names, UserConfig::get("character"));
GetNames("corporation", $names, UserConfig::get("corporation"));
GetNames("alliance", $names, UserConfig::get("alliance"));
GetNames("faction", $names, UserConfig::get("faction"));
GetNames("ship", $names, UserConfig::get("ship"));
GetNames("systems", $names, UserConfig::get("system"));
GetNames("regions", $names, UserConfig::get("region"));

$ids["combined"] = true;
$ids["limit"] = 100;

$pageTitle = "Tracking";

$kills = Kills::getKills($ids);
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