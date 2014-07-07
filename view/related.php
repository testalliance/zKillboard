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

$systemID = (int) $system;
$relatedTime = (int) $time;

$systemName = Info::getSystemName($systemID);
$regionName = Info::getRegionName(Info::getRegionIDFromSystemID($systemID));
$unixTime = strtotime($relatedTime);
$time = date("Y-m-d H:i", $unixTime);

if (((int) $exHours) < 1 || ((int) $exHours > 12)) $exHours = 1;

$key = "$systemID:$relatedTime:$exHours";

$key = "|$systemID:$relatedTime:$exHours";
$mc = Cache::get($key);
if (!$mc) {
	$parameters = array("solarSystemID" => $systemID, "relatedTime" => $relatedTime, "exHours" => $exHours);
	$kills = Kills::getKills($parameters);
	$summary = Related::buildSummary($kills, $parameters, "$systemName:$time:$exHours");
	$mc = array("summary" => $summary, "systemName" => $systemName, "regionName" => $regionName, "time" => $time, "exHours" => $exHours);
	Cache::set($key, $mc, 300);
}

$app->render("related.html", $mc);
