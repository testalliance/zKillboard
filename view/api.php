<?php

scrapeCheck();

$parameters = Util::convertUriToParameters();

// Enforcement
if (sizeof($parameters) < 2) die("Invalid request.  Must provide at least two request parameters");
// At least one of these modifiers is required
$requiredM = array("characterID", "corporationID", "allianceID", "factionID", "shipTypeID", "groupID", "solarSystemID", "regionID", "solo", "w-space");
$hasRequired = false;
foreach($requiredM as $required) {
	$hasRequired |= array_key_exists($required, $parameters);
}
if (!$hasRequired) throw new Exception("Must pass at least two required modifier.  Please read API Information.");

$return = Feed::getKills($parameters);

$array = array();
foreach($return as $json) $array[] = json_decode($json, true);
$app->etag(md5(serialize($return)));
$app->expires("+1 hour");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
if(isset($parameters["xml"]))
{
	$app->contentType("text/xml; charset=utf-8");
	echo xmlOut($array, $parameters);
}
elseif(isset($_GET["callback"]))
{
	if(is_valid_callback($_GET["callback"]))
	{
		$app->contentType("application/json; charset=utf-8");
		header("X-JSONP: true");
		echo $_GET["callback"] . "(" . json_encode($array) .")";
	}
}
else
{
	$app->contentType("application/json; charset=utf-8");
	echo json_encode($array);
}

function is_valid_callback($subject)
{
	$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

	$reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
	'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 
	'for', 'switch', 'while', 'debugger', 'function', 'this', 'with', 
	'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 
	'extends', 'super', 'const', 'export', 'import', 'implements', 'let', 
	'private', 'public', 'yield', 'interface', 'package', 'protected', 
	'static', 'null', 'true', 'false');

	return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
}

function xmlOut($array, $parameters)
{
	$xml = '<?xml version="1.0" encoding="UTF-8"?>';
	$xml .= '<eveapi version="2" zkbapi="1">';
	$date = date("Y-m-d H:i:s");
	$cachedUntil = date("Y-m-d H:i:s", strtotime("+1 hour"));

	$xml .= '<currentTime>'.$date.'</currentTime>';
	$xml .= '<result>';
	if(!empty($array))
	{
		$xml .= '<rowset name="kills" key="killID" columns="killID,solarSystemID,killTime,moonID">';
		foreach($array as $kill)
		{
			$xml .= '<row killID="'.$kill["killID"].'" solarSystemID="'.$kill["solarSystemID"].'" killTime="'.$kill["killTime"].'" moonID="'.$kill["moonID"].'">';
			$xml .= '<victim characterID="'.$kill["victim"]["characterID"].'" characterName="'.$kill["victim"]["characterName"].'" corporationID="'.$kill["victim"]["corporationID"].'" corporationName="'.$kill["victim"]["corporationName"].'" allianceID="'.$kill["victim"]["allianceID"].'" allianceName="'.$kill["victim"]["allianceName"].'" factionID="'.$kill["victim"]["factionID"].'" factionName="'.$kill["victim"]["factionName"].'" damageTaken="'.$kill["victim"]["damageTaken"].'" shipTypeID="'.$kill["victim"]["shipTypeID"].'"/>';
			if(!isset($parameters["no-attackers"]))
			{
				$xml .= '<rowset name="attackers" columns="characterID,characterName,corporationID,corporationName,allianceID,allianceName,factionID,factionName,securityStatus,damageDone,finalBlow,weaponTypeID,shipTypeID">';
				foreach($kill["attackers"] as $attacker)
					$xml .= '<row characterID="'.$attacker["characterID"].'" characterName="'.$attacker["characterName"].'" corporationID="'.$attacker["corporationID"].'" corporationName="'.$attacker["corporationName"].'" allianceID="'.$attacker["allianceID"].'" allianceName="'.$attacker["allianceName"].'" factionID="'.$attacker["factionID"].'" factionName="'.$attacker["factionName"].'" securityStatus="'.$attacker["securityStatus"].'" damageDone="'.$attacker["damageDone"].'" finalBlow="'.$attacker["finalBlow"].'" weaponTypeID="'.$attacker["weaponTypeID"].'" shipTypeID="'.$attacker["shipTypeID"].'"/>';
				$xml .= '</rowset>';
			}
			if(!isset($parameters["no-items"]))
			{
				$xml .= '<rowset name="items" columns="typeID,flag,qtyDropped,qtyDestroyed">';
				foreach($kill["items"] as $item)
					$xml .= '<row typeID="'.$item["typeID"].'" flag="'.$item["flag"].'" qtyDropped="'.$item["qtyDropped"].'" qtyDestroyed="'.$item["qtyDestroyed"].'"/>';
				$xml .= '</rowset>';
			}
			$xml .= '</row>';
		}
		$xml .= '</rowset>';
	}
	else
	{
		$cachedUntil = date("Y-m-d H:i:s", strtotime("+5 minutes"));
		$xml .= "<error>No kills available</error>";
	}
	$xml .= '</result>';
	$xml .= '<cachedUntil>'.$cachedUntil.'</cachedUntil>';
	$xml .= '</eveapi>';
	return $xml;
}

function scrapeCheck() {
	global $app;
	$timeLimit = 60; // Number of seconds allowed between requests
	$numAccesses = 10; // Number of accesses before hammer is thrown

	$ip = IP::get();
	
	$validScrapers = array(
		"85.88.24.82", // DOTLAN
	);
	$isValidScraper = false;
	foreach ($validScrapers as $validScraper) {
		if (strpos($ip, $validScraper) !== false) $isValidScraper = true;
	}
	if ($isValidScraper == false) {
		$session = Memcached::get("session_$ip");
		if ($session == null) {
			$session = array("accesses" => array());
		}
		$oldAccess = 0;
		foreach($session["accesses"] as $access) {
			if ($access < (time() - $timeLimit)) $oldAccess++;
		}
		$session["accesses"][] = time();
		$session["last_access"] = time();
		Memcached::set("session_$ip", $session, $timeLimit + $oldAccess);
		if (sizeof($session["accesses"]) - $oldAccess >= 10 || sizeof($session["accesses"]) > $numAccesses ) {
			Log::log("$ip has hit the scrape limit, adding them to the naughty list.");
			throw new Exception("Hammering the API isn't very nice.  Please keep your requests $timeLimit seconds apart.  Thank you.");
		}
	}
}
