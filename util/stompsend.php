<?php

$base = dirname(__FILE__);
require_once "$base/../init.php";

$stomp = new Stomp("tcp://10.10.10.197:61613", "zkb", "myp4ss1sm1n3");

$stompKey = "StompSend::lastFetch";
$lastFetch = time() - (12 * 3600);
$lastFetch = Storage::retrieve($stompKey, $lastFetch);

$result = Db::query("SELECT killID, unix_timestamp(insertTime) AS insertTime, kill_json FROM zz_killmails WHERE killID > 0 AND insertTime > from_unixtime(:lastFetch) ORDER BY killID", array(":lastFetch" => $lastFetch), 0);

$lastFetch = time();
Storage::store($stompKey, $lastFetch);

foreach($result as $kill)
{
	$destinations = Destinations($kill["kill_json"]);
	$destinations = join(",", $destinations);
	$lastFetch = max($lastFetch, $kill["insertTime"]);
	if(!empty($kill["kill_json"]))
		$stomp->send($destinations, $kill["kill_json"]);
}
Log::log("Sent out " . sizeof($result) . " killmails via stomp");

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
