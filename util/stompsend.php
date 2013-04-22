<?php

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
				$stomp->send($destinations, $kill["kill_json"]);

			// Send out stuff for the live starmap
			$data = json_decode($kill["kill_json"], true);
			$json = json_encode(array("solarSystemID" => $data["solarSystemID"], "killID" => $data["killID"], "shipTypeID" => $data["victim"]["shipTypeID"], "killTime" => $data["killTime"]));
			$stomp->send("/topic/starmap.systems.active", $json);
		}
	}
	//if(sizeof($result) > 0) Log::log("Sent out " . sizeof($result) . " killmails via stomp (Including pings to the starmap) (Count includes manual mails, but only killIDs LARGER than 0 (api) is sent to all the stomp routes)");
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
