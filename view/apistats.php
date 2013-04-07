<?php

//make sure the requester is not being a naughty boy
Util::scrapeCheck();

//make sure the type is allowed - and map it to our internal string
$allowed_types = array('character' => 'pilot','corporation' => 'corp','alliance' => 'alli','faction' => 'faction');

//make sure its an allowed call
if (!array_key_exists($type, $allowed_types)) throw new Exception("Must pass a valid type.  Please read API Information.");

//make sure we have been passed an id - thats a number
if (!is_numeric($id)) throw new Exception("Must pass a valid id.  Please read API Information.");

//get the statistics for our target character/corp/allaice/faction
$stat_totals  = Db::query('SELECT SUM(destroyed) AS countDestroyed, SUM(lost) AS countLost, SUM(pointsDestroyed) AS pointsDestroyed, SUM(pointsLost) AS pointsLost, SUM(iskDestroyed) AS iskDestroyed, SUM(iskLost) AS iskLost FROM zz_stats WHERE type = :type AND typeID = :id', array(':type' => $allowed_types[$type], ':id' => $id));
$stat_details = Db::query('SELECT groupID, destroyed AS countDestroyed, lost AS countLost, pointsDestroyed, pointsLost, iskDestroyed, iskLost FROM zz_stats WHERE type = :type AND typeID = :id', array(':type' => $allowed_types[$type], ':id' => $id));

//build our output data
$output['totals'] = $stat_totals[0];
foreach($stat_details as $detail) $output['groups'][array_shift($detail)] = $detail;
		
//set the headers to cache the request properly
$app->etag(md5(serialize($output)));
$app->expires("+1 hour");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

//now display the output in a way that is useable
switch (strtolower($return_method))
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