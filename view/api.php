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

//make sure the requester is not being a naughty boy
Util::scrapeCheck();

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
elseif(isset($_GET["callback"]) && Util::isValidCallback($_GET["callback"]) )
{
	$app->contentType("application/json; charset=utf-8");
	header("X-JSONP: true");
	echo $_GET["callback"] . "(" . json_encode($array, JSON_NUMERIC_CHECK) .")";
}
else
{
	$app->contentType("application/json; charset=utf-8");
	if(isset($parameters["pretty"]))
		echo json_encode($array, JSON_PRETTY_PRINT);
	else
		echo json_encode($array, JSON_NUMERIC_CHECK);
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
			$xml .= '<row killID="'.(int) $kill["killID"].'" solarSystemID="'.(int) $kill["solarSystemID"].'" killTime="'.$kill["killTime"].'" moonID="'.(int) $kill["moonID"].'">';
			$xml .= '<victim characterID="'.(int) $kill["victim"]["characterID"].'" characterName="'.$kill["victim"]["characterName"].'" corporationID="'.(int) $kill["victim"]["corporationID"].'" corporationName="'.$kill["victim"]["corporationName"].'" allianceID="'.(int) $kill["victim"]["allianceID"].'" allianceName="'.$kill["victim"]["allianceName"].'" factionID="'.(int) $kill["victim"]["factionID"].'" factionName="'.$kill["victim"]["factionName"].'" damageTaken="'.(int) $kill["victim"]["damageTaken"].'" shipTypeID="'.(int) $kill["victim"]["shipTypeID"].'"/>';
			if(!isset($parameters["no-attackers"]) && !empty($kill["attackers"]))
			{
				$xml .= '<rowset name="attackers" columns="characterID,characterName,corporationID,corporationName,allianceID,allianceName,factionID,factionName,securityStatus,damageDone,finalBlow,weaponTypeID,shipTypeID">';
				foreach($kill["attackers"] as $attacker)
					$xml .= '<row characterID="'.(int) $attacker["characterID"].'" characterName="'.$attacker["characterName"].'" corporationID="'.(int) $attacker["corporationID"].'" corporationName="'.$attacker["corporationName"].'" allianceID="'.(int) $attacker["allianceID"].'" allianceName="'.$attacker["allianceName"].'" factionID="'.(int) $attacker["factionID"].'" factionName="'.$attacker["factionName"].'" securityStatus="'. (float) $attacker["securityStatus"].'" damageDone="'.(int) $attacker["damageDone"].'" finalBlow="'.(int) $attacker["finalBlow"].'" weaponTypeID="'.(int) $attacker["weaponTypeID"].'" shipTypeID="'.(int) $attacker["shipTypeID"].'"/>';
				$xml .= '</rowset>';
			}
			if(!isset($parameters["no-items"]) && !empty($kill["items"]))
			{
				$xml .= '<rowset name="items" columns="typeID,flag,qtyDropped,qtyDestroyed">';
				foreach($kill["items"] as $item)
					$xml .= '<row typeID="'.(int) $item["typeID"].'" flag="'.(int) $item["flag"].'" qtyDropped="'.(int) $item["qtyDropped"].'" qtyDestroyed="'.(int) $item["qtyDestroyed"].'"/>';
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
