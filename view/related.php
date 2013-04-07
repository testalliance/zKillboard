<?php
$systemID = (int) $system;
$relatedTime = (int) $time;

$systemName = Info::getSystemName($systemID);
$unixTime = strtotime($relatedTime);
$time = date("Y-m-d H:i", $unixTime);

$parameters = array("solarSystemID" => $systemID, "relatedTime" => $relatedTime, "excludeSubdomain" => true);
$kills = Kills::getKills($parameters);
$summary = Summary::buildSummary($kills, $parameters, "$systemName:$time");

$app->render("related.html", array("summary" => $summary, "systemName" => $systemName, "time" => $time));
