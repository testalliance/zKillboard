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

//make sure the type is allowed - and map it to our internal string
$allowed_types = array('factionID' => 'faction', 'allianceID' => 'alli', 'corporationID' => 'corp', 'characterID' => 'pilot', 'groupID' => 'group', 'shipID' => 'ship', 'systemID' => 'system', 'regionID' => 'region');

//parse the flags
foreach($flags as $flag) { 
	//a numeric flag is a our targets id
	if (is_numeric($flag)) { $id = $flag; }
	
	//if the flag is in our allowed_types - treat it as a type
	if (array_key_exists($flag, $allowed_types)) { $type = $allowed_types[$flag]; }
}

//other flags are easier to handle like so
$stats_table = ((in_array('recent', $flags)) ? 'zz_stats_recent' : 'zz_stats');
$output_type = ((in_array('xml', $flags)) ? 'xml' : 'json');

//make sure we have an allowed call
if (!isset($id)) { throw new Exception("Must pass a valid id.  Please read API Information."); }
if (!isset($type)) { throw new Exception("Must pass a valid type.  Please read API Information."); }

//get the statistics for our target type
$stat_totals  = Db::query('SELECT SUM(destroyed) AS countDestroyed, SUM(lost) AS countLost, SUM(pointsDestroyed) AS pointsDestroyed, SUM(pointsLost) AS pointsLost, SUM(iskDestroyed) AS iskDestroyed, SUM(iskLost) AS iskLost FROM ' . $stats_table . ' WHERE type = :type AND typeID = :id', array(':type' => $type, ':id' => $id));
$stat_details = Db::query('SELECT groupID, destroyed AS countDestroyed, lost AS countLost, pointsDestroyed, pointsLost, iskDestroyed, iskLost FROM ' . $stats_table . ' WHERE type = :type AND typeID = :id', array(':type' => $type, ':id' => $id));

//build our output data
$output['totals'] = $stat_totals[0];
foreach($stat_details as $detail) $output['groups'][array_shift($detail)] = $detail;
		
//set the headers to cache the request properly
$app->etag(md5(serialize($output)));
$app->expires("+1 hour");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

//now display the output in a way that is useable
switch (strtolower($output_type))
{
	case 'json':
	
		$app->contentType("application/json; charset=utf-8");
		
		if(isset($_GET["callback"]) && Util::isValidCallback($_GET["callback"]) )
		{
			header("X-JSONP: true");
			echo $_GET["callback"] . '(' . json_encode($output, JSON_NUMERIC_CHECK) . ')';
		}
		else
		{
			echo json_encode($output, JSON_NUMERIC_CHECK);
		}
		
	break;

	case 'xml':
	
		$app->contentType("text/xml; charset=utf-8");
		echo xmlOut($output);
	
	break;
	
	default:
	
		throw new Exception("Invalid return type.  Please read API Information.");
	
	break;
}

function xmlOut($output)
{
	//define how long it should be cached for
	$cachedUntil = date("Y-m-d H:i:s", strtotime("+1 hour"));
	
	//start building our xml document
	$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
	$xml .= '<eveapi version="2" zkbapi="1">';
	$xml .= '<currentTime>' . date('Y-m-d H:i:s') . '</currentTime>';
	$xml .= '<result>';
	
	if(!empty($output))
	{
		//first send the totals
		$xml .= '<rowset name="totals" key="type" columns="type,destroyed,lost">';
			$xml .= '<row type="count" destroyed="' . $output['totals']['countDestroyed'] . '" lost="' . $output['totals']['countLost'] . '" />';
			$xml .= '<row type="points" destroyed="' . $output['totals']['pointsDestroyed'] . '" lost="' . $output['totals']['pointsLost'] . '" />';
			$xml .= '<row type="isk" destroyed="' . $output['totals']['iskDestroyed'] . '" lost="' . $output['totals']['iskLost'] . '" />';
		$xml .= '</rowset>';
			
		//and now the groups
		$xml .= '<rowset name="groups" key="groupID" columns="groupID,countDestroyed,countLost,pointsDestroyed,pointsLost,iskDestroyed,iskLost">';
		
		foreach($output['groups'] as $group_id => $group_details)
		{
			$xml .= '<row groupID="' . $group_id . '" countDestroyed="' . $group_details['countDestroyed'] . '" countLost="' . $group_details['countLost'] . '" pointsDestroyed="' . $group_details['pointsDestroyed'] . '" pointsLost="' . $group_details['pointsLost'] . '" iskDestroyed="' . $group_details['iskDestroyed'] . '" iskLost="' . $group_details['iskLost'] . '" />';
		}
		
		$xml .= '</rowset>';
	}
	else
	{
		$cachedUntil = date("Y-m-d H:i:s", strtotime("+5 minutes"));
		$xml .= "<error>No data available</error>";
	}
	$xml .= '</result>';
	$xml .= '<cachedUntil>' . $cachedUntil . '</cachedUntil>';
	$xml .= '</eveapi>';
	return $xml;
}