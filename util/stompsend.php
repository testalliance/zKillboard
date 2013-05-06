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

$base = dirname(__FILE__);
require_once "$base/../init.php";

global $stompServer, $stompUser, $stompPassword;

$stomp = new Stomp($stompServer, $stompUser, $stompPassword);

$stompKey = "StompSend::lastFetch";
$lastFetch = time() - (12 * 3600);

$lastFetch = Storage::retrieve($stompKey, $lastFetch);
for ($i = 0; $i < 11; $i++) {
	$result = Db::query("SELECT killID, unix_timestamp(insertTime) AS insertTime, kill_json FROM zz_killmails WHERE insertTime > from_unixtime(:lastFetch) ORDER BY killID", array(":lastFetch" => $lastFetch), 0);

	$lastFetch = time();
	Storage::store($stompKey, $lastFetch);

	foreach($result as $kill)
	{
		$destinations = Destinations($kill["kill_json"]);
		$destinations = join(",", $destinations);
		$lastFetch = max($lastFetch, $kill["insertTime"]);
		if(!empty($kill["kill_json"]))
		{
			if($kill["killID"] > 0)
			{
				$stomp->send($destinations, $kill["kill_json"]);
			}
			// Send out stuff for the live starmap
			$data = json_decode($kill["kill_json"], true);
			$json = json_encode(array("solarSystemID" => $data["solarSystemID"], "killID" => $data["killID"], "shipTypeID" => $data["victim"]["shipTypeID"], "killTime" => $data["killTime"]));
			$stomp->send("/topic/starmap.systems.active", $json);
		}
	}
	if(sizeof($result) > 0) Log::log("Stomped " . sizeof($result) . " killmails");
	sleep(5);
}

function Destinations($kill)
{
	$kill = json_decode($kill, true);
	$destinations = array();

	$destinations[] = "/topic/kills";
	$destinations[] = "/queue/kills";
	$destinations[] = "/topic/location.solarsystem.".$kill["solarSystemID"];

	// victim
	if($kill["victim"]["characterID"] > 0)
		$destinations[] = "/topic/involved.character.".$kill["victim"]["characterID"];
	if($kill["victim"]["corporationID"] > 0)
		$destinations[] = "/topic/involved.corporation.".$kill["victim"]["corporationID"];
	if($kill["victim"]["factionID"] > 0)
		$destinations[] = "/topic/involved.faction.".$kill["victim"]["factionID"];
	if($kill["victim"]["allianceID"] > 0)
		$destinations[] = "/topic/involved.alliance.".$kill["victim"]["allianceID"];

	// attackers
	foreach($kill["attackers"] as $attacker)
	{
		if($attacker["characterID"] > 0)
			$destinations[] = "/topic/involved.character." . $attacker["characterID"];
		if($attacker["corporationID"] > 0)
			$destinations[] = "/topic/involved.corporation." . $attacker["corporationID"];
		if($attacker["factionID"] > 0)
			$destinations[] = "/topic/involved.faction." . $attacker["factionID"];
		if($attacker["allianceID"] > 0)
			$destinations[] = "/topic/involved.alliance." . $attacker["allianceID"];
	}

	return $destinations;
}
