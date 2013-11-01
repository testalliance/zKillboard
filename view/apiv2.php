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

// Figure out some defaults
$lookup = $flags[0]; // What the user wants to look at
$hash = $flags[1]; // The hash of the user
unset($flags[0]); // Unset lookup
unset($flags[1]); // Unset the hash
$ip = IP::get(); // get the IP
$agent = $_SERVER["HTTP_USER_AGENT"]; // user agent
$xml = false; // Nope, not XML
$jsonp = false; // Nope, not a jsonp request
$userID = zKBApi::checkHash($hash); // userID

// Reset the array to 0
$flags = array_values($flags);

// Create parameters
$params = params($flags);

// Figure out if it's XML or not
if(strpos($_SERVER["REQUEST_URI"], "xml") !== false)
	$xml = true;

// Figure out if it's a jsonp call.
if(isset($_GET["callback"]) && Util::isValidCallback($_GET["callback"]))
	$jsonp = true;
if(isset($_GET["callback"]) && !Util::isValidCallback($_GET["callback"]))
	error(array(array("Invalid callback", 9)), $xml);

// Check if the hash is valid
if(is_null($userID))
	error(array(array("Invalid Hash", 0)), $xml);

switch($lookup)
{
	case "dna":
		$params["limit"] = 2;
		$params["cacheTime"] = 3600;
		$kills = Feed::getKills($params);
		$dna = array();
		foreach($kills as $kill)
		{
			$kill = json_decode($kill, true);
			$killdata = Kills::getKillDetails($kill["killID"]);
			$dna[] = array(
				"killtime" => $killdata["info"]["dttm"], 
				"SolarSystemName" => $killdata["info"]["solarSystemName"],
				"solarSystemID" => $killdata["info"]["solarSystemID"],
				"regionID" => $killdata["info"]["regionID"],
				"regionName" => $killdata["info"]["regionName"],
				"victimCharacterID" => (isset($killdata["victim"]["characterID"]) ? isset($killdata["victim"]["characterID"]) : null),
				"victimCharacterName" => (isset($killdata["victim"]["characterName"]) ? isset($killdata["victim"]["characterName"]) : null),
				"victimCorporationID" => (isset($killdata["victim"]["corporationID"]) ? isset($killdata["victim"]["corporationID"]) : null),
				"victimCorporationName" => (isset($killdata["victim"]["corporationName"]) ? isset($killdata["victim"]["corporationName"]) : null),
				"victimAllianceID" => (isset($killdata["victim"]["allianceID"]) ? isset($killdata["victim"]["allianceID"]) : null),
				"victimAllianceName" => (isset($killdata["victim"]["allianceName"]) ? isset($killdata["victim"]["allianceName"]) : null),
				"victimFactionID" => (isset($killdata["victim"]["factionID"]) ? isset($killdata["victim"]["factionID"]) : null),
				"victimFactionName" => (isset($killdata["victim"]["factionName"]) ? isset($killdata["victim"]["factionName"]) : null),
				"dna" => Fitting::DNA($killdata["items"],$killdata["victim"]["shipTypeID"]));
		}
		header("Content-Type: application/json");
		outputData($dna, false, $params, $ip, $agent, $userID, false, $jsonp);
	break;

	case "stats":
		die("to be implemented");
	break;

	case "kills":
		$params["kills"] = true;
		$return = Feed::getKills($params);
		outputData($return, $xml, $params, $ip, $agent, $userID, true, $jsonp);
	break;

	case "losses":
		$params["losses"] = true;
		$return = Feed::getKills($params);
		outputData($return, $xml, $params, $ip, $agent, $userID, true, $jsonp);
	break;

	case "combined":
		$return = Feed::getKills($params);
		outputData($return, $xml, $params, $ip, $agent, $userID, true, $jsonp);
	break;

	case "w-space":
		$params["w-space"] = true;
		$return = Feed::getKills($params);
		outputData($return, $xml, $params, $ip, $agent, $userID, true, $jsonp);
	break;

	case "solo":
		$params["solo"] = true;
		$return = Feed::getKills($params);
		outputData($return, $xml, $params, $ip, $agent, $userID, true, $jsonp);
	break;
}

function error($error, $xml)
{
	$date = date("Y-m-d H:i:s");
	$json = array();

	if($xml)
	{
		$data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$data .= "<eveapi version=\"2\" zkbapi=\"2\">";
		$data .= "<currentTime>$date</currentTime>";
		$data .= "<result>";
		foreach($error as $r)
		{
			$message = $r[0];
			$errorCode = $r[1];
			$data .= "<row>";
			$data .= "<message>$message</message>";
			$data .= "<errorCode>$errorCode</errorCode>";
			$data .= "</row>";
		}
		$data .= "</result>";
		$data .= "<cachedUntil>$date</cachedUntil>";
		$data .= "</eveapi>";
		header("Content-type: text/xml; charset=utf-8");
		echo $data;
	}
	else
	{
		foreach($error as $r)
		{
			$message = $r[0];
			$errorCode = $r[1];
			$json[] = array("message" => $message, "errorCode" => $errorCode, "currentTime" => $date, "cachedUntil" => $date);
		}
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($json);
	}
	die();
}

function params($flags)
{
	$error = array();
	$currentIndex = 0;
	foreach($flags as $key)
	{
		$value = $currentIndex + 1 < sizeof($flags) ? $flags[$currentIndex + 1] : null;
		switch($key)
		{
			case "characterID":
			case "corporationID":
			case "allianceID":
			case "factionID":
			case "shipTypeID":
			case "groupID":
			case "solarSystemID":
			case "regionID":
				if ($value != null) {
					if (strpos($key, "ID") === false) $key = $key . "ID";
					if ($key == "systemID") $key = "solarSystemID";
					else if ($key == "shipID") $key = "shipTypeID";
					$exploded = explode(",", $value);
					foreach($exploded as $aValue) {
						if ($aValue != (int) $aValue || ((int) $aValue) == 0) $error[] = array("Invalid ID passed: $aValue!", 1);
					}
					if (sizeof($exploded) > 10) $error[] = array("Too many IDs! Max: 10", 2);
					$parameters[$key] = $exploded;
				}
			break;
			case "page":
				$value = (int)$value;
				if ($value < 1) $error[] = array("Page must be greater than or equal to 1", 3);
				$parameters[$key] = $value;
			break;
			case "orderDirection":
				if (!($value == "asc" || $value == "desc")) $error[] = array("Invalid orderDirection! Allowed: asc, desc", 4);
				$parameters[$key] = "desc";
				$parameters[$key] = $value;
			break;
			case "pastSeconds":
				$value = (int) $value;
				if (($value / 86400) > 7) $error[] = array("pastSeconds is limited to a maximum of 7 days", 5);
				$parameters[$key] = $value;
			break;
			case "startTime":
			case "endTime":
				$time = strtotime($value);
				if($time < 0) $error[] = array("$value is not a valid time format", 6);
				$parameters[$key] = $value;
			break;
			case "limit":
				$value = (int) $value;
				if($value <= 200) $parameters[$key] = $value;
				if($value <= 0) $parameters[$key] = 1;
				else $parameters[$key] = 200;
			break;
			case "beforeKillID":
			case "afterKillID":
				if (!is_numeric($value)) $error[] = array("$value is not a valid entry for $key", 7);
				$parameters[$key] = (int) $value;
			break;
			case "xml":
				$parameters[$key] = true;
			break;
		}
		$currentIndex++;
	}
	
	if($error)
		error($error, isset($parameters["xml"]));

	return @$parameters;
}

function xmlOut($array, $parameters)
{
	$xml = '<?xml version="1.0" encoding="UTF-8"?>';
	$xml .= '<eveapi version="2" zkbapi="1">';
	$date = date("Y-m-d H:i:s");
	$cachedUntil = date("Y-m-d H:i:s", strtotime("+30 minutes"));

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

function outputData($data, $xml, $params, $ip, $agent, $userID, $decode = true, $jsonp)
{
	// Time till tomorrow
	$timetilltomorrow = strtotime('tomorrow') - time();
	header("X-Seconds-Till-Tomorrow: $timetilltomorrow");

	// Fetch AccessAmount
	$accessAllowed = zKBApi::accessAllowed($userID);
	header("X-AccessAllowed: $accessAllowed");

	// Check if tomorrow has come and gone
	$currentDate = date("Y-m-d H:i:s");
	$lastAccessDate = zKBApi::lastAccess($userID);

	if($lastAccessDate < $currentDate)
		zKBApi::resetAccess($userID);

	// header for accessCount
	header("X-AccessCount: ". zKBApi::accessCount($userID));
	header("X-Seconds-Between-Req: ". ceil(($timetilltomorrow / zKBApi::accessAllowed($userID))));

	// check if apiAccess is larger or equal accessAllowed (if it's larger, something went wrong somewhere, but lets catch it regardless)
	if(zKBApi::accessCount($userID) >= $accessAllowed)
		error(array(array("You have zero access calls left. Try again in: $timetilltomorrow seconds", 8)), $xml);

	// Decrease accessAmount
	zKBApi::incrementAccess($userID);

	// Moar headers
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods: GET");

	// Decode the json and shove it into an array
	if($decode)
		foreach($data as $key => $val)
			$data[$key] = json_decode($val, true);

	// Jsonp uses json, so you can't use xml with jsonp, duh..
	if($jsonp)
		$xml = false;

	// The Output!
	if($xml)
	{
		header("Content-Type: text/xml; charset=utf-8");
		echo xmlOut($data, $params);
		die();
	}
	else
	{
		header("Content-Type: application/json; charset=utf-8");
		if($jsonp)
		{
			header("X-JSONP: true");
			echo $_GET["callback"] . "(" . json_encode($data, JSON_NUMERIC_CHECK) .")";
		}
		else
		{
			echo json_encode($data, JSON_NUMERIC_CHECK);
		}
		die();
	}
}