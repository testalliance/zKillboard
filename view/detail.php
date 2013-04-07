<?php
$involved = array();
$message = "";
$pageID = "detail:$id";

if($_POST && !User::isRevoked())
{
	$comment = "";
	$characterID = "";
	if(isset($_POST["comment"]))
		$comment = $_POST["comment"];
	if(isset($_POST["characterID"]))
		$characterID = $_POST["characterID"];
	if(!$comment)
		$message = "You didn't write anything in the comment field, try again";
	if (isset($comment) && isset($characterID)) {
		$message = Comments::addComment($comment, $characterID, $pageID);
	}
}

if($_POST && User::isRevoked())
{
	$app->render("revoked.html");
	$app->stop();
}

if ($id < 0) {
	// See if this manual mail has an api verified version
	$mKillID = -1 * $id;
	$killID = Db::queryField("select killID from zz_manual_mails where mKillID = :mKillID", "killID", array(":mKillID" => $mKillID), 1);
	if ($killID > 0) {
		header("Location: /detail/$killID");
		die();
	}
}

// Create the details on this kill
$killdata = Kills::getKillDetails($id);

if (sizeof($killdata["victim"]) == 0) {
	return $app->render("message.html", array("message"=> "KillID $id does not exist."));
}

// create the dropdown involved array
$allinvolved = $killdata["involved"];
$cnt = 0;
while($cnt < 10)
{
	if(isset($allinvolved[$cnt]))
	{
		$involved[] = $allinvolved[$cnt];
		unset($allinvolved[$cnt]);
	}
	$cnt++;
	continue;
}
$topDamage = $finalBlow = null;
if (sizeof($killdata["involved"]) > 1){
	$topDamage = $killdata["involved"][0];
	foreach($killdata["involved"] as $inv) {
		if ($inv["finalBlow"] == 1) $finalBlow = $inv;
	}
}

// And now give all the arrays and whatnots to twig..
if($pageview == "overview")
{
	$extra["items"] = combineditems(md5($id), $killdata["items"]);
	$extra["invAll"] = involvedCorpsAndAllis(md5($id), $killdata["involved"]);
	$extra["involved"] = $involved;
	$extra["allinvolved"] = $allinvolved;
}
if($pageview == "comments")
{
	$extra["cmtChars"] = Api::getCharacters();
	$extra["cmtChars"][] = array("characterID" => 0, "characterName" => "Anonymous");
}

$extra["droppedisk"] = droppedIsk(md5($id), $killdata["items"]);
$extra["lostisk"] = $killdata["info"]["total_price"] - $extra["droppedisk"];
$comments = Comments::getPageComments($pageID);
$extra["comments"] = Info::addInfo($comments);
$extra["comments"]["count"] = Comments::getPageCommentCount($pageID);
$extra["relatedtime"] = date("YmdH00", strtotime($killdata["info"]["killTime"]));
$extra["fittingwheel"] = eftarray(md5($id), $killdata["items"]);
$extra["involvedships"] = involvedships($killdata["involved"]);
$extra["involvedshipscount"] = count($extra["involvedships"]);
$extra["totalprice"] = usdeurgbp($killdata["info"]["total_price"]);
$extra["destroyedprice"] = usdeurgbp($extra["lostisk"]);
$extra["droppedprice"] = usdeurgbp($extra["droppedisk"]);
$extra["efttext"] = EFT::getText($extra["fittingwheel"]);
$extra["rawmail"] = EPIC::getMail($id);
$extra["slotCounts"] = Info::getSlotCounts($killdata["victim"]["shipTypeID"]);

$url = "https://". $_SERVER["SERVER_NAME"] ."/detail/$id/";
$app->render("detail.html", array("pageview" => $pageview, "killdata" => $killdata, "extra" => $extra, "message" => $message, "flags" => Info::$effectToSlot, "topDamage" => $topDamage, "finalBlow" => $finalBlow, "url" => $url));

function involvedships($array)
{
	$involved = array();
	foreach($array as $inv)
	{
		if(isset($involved[$inv["shipTypeID"]]) && isset($inv["shipName"]))
			$involved[$inv["shipTypeID"]] = array("shipName" => $inv["shipName"], "shipTypeID" => $inv["shipTypeID"], "count" => $involved[$inv["shipTypeID"]]["count"] + 1);
		elseif(isset($inv["shipTypeID"]) && isset($inv["shipName"]))
		{
			$involved[$inv["shipTypeID"]] = array("shipName" => $inv["shipName"], "shipTypeID" => $inv["shipTypeID"], "count" => 1);
		}
		else
			continue;
	}
	return $involved;
}

function usdeurgbp($totalprice)
{
    $usd = 17;
    $eur = 13;
    $gbp = 10;
    $plex = Price::getItemPrice("29668");
    $usdval = $plex / $usd;
    $eurval = $plex / $eur;
    $gbpval = $plex / $gbp;
    
    return array("usd" => $totalprice / $usdval, "eur" => $totalprice / $eurval, "gbp" => $totalprice / $gbpval);
}

function eftarray($md5, $items)
{
	$memcached = Memcached::get($md5."eftarray");
	if ($memcached) return $memcached;

	// EFT / Fitting Wheel
	$eftarray["high"] = array(); // high
	$eftarray["mid"] = array(); // mid
	$eftarray["low"] = array(); // low
	$eftarray["rig"] = array(); // rig
	$eftarray["drone"] = array(); // drone
	$eftarray["sub"] = array(); // sub
	$eftammo["high"] = array(); // high ammo
	$eftammo["mid"] = array(); // mid ammo

	foreach($items as $itm)
	{
		if (!isset($itm["flagName"])) $itm["flagName"] = Info::getFlagName($itm["flag"]);
		$key = $itm["typeName"] . "|" . $itm["flagName"];
		if(isset($itm["flagName"]))
		{
			if($itm["fittable"]) // not ammo or whatever
			{
				if($itm["flagName"] == "High Slots")
					$eftarray["high"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"]);
				if($itm["flagName"] == "Mid Slots")
					$eftarray["mid"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"]);
				if($itm["flagName"] == "Low Slots")
					$eftarray["low"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"]);
				if($itm["flagName"] == "Rigs")
					$eftarray["rig"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"]);
				if($itm["flagName"] == "SubSystems")
					$eftarray["sub"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"]);
			}
			else
			{
				if($itm["flagName"] == "Drone Bay")
					$eftarray["drone"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"], "qty" => $itm["qtyDropped"] + $itm["qtyDestroyed"]);
			}
		}
	}

	// Ammo shit
	foreach($items as $itm) {
		if(!$itm["fittable"] && isset($itm["flagName"])) // possibly ammo
		{
			if($itm["flagName"] == "High Slots") // high slot ammo
				$eftarray["high"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"], "charge" => true);
			if($itm["flagName"] == "Mid Slots") // mid slot ammo
				$eftarray["mid"][$itm["flag"]][] = array("typeName" => $itm["typeName"], "typeID" => $itm["typeID"], "charge" => true);
		}
	}
	foreach($eftarray as $key=>$value) {
		if (sizeof($value)) {
			asort($value);
			$eftarray[$key] = $value;
		} else unset($eftarray[$key]);
	}
	Memcached::set($md5."eftarray", $eftarray);
	return $eftarray;
}

function combineditems($md5, $items)
{
	$memcached = Memcached::get($md5."combineditems");
	if($memcached) return $memcached;

	// Create the new item array with combined items and whatnot
	$itemList = array();
	foreach($items as $itm)
	{
		if (!isset($itm["flagName"])) $itm["flagName"] = Info::getFlagName($itm["flag"]);
		for ($i = 0; $i <= 1; $i++) {
			$mItem = $itm;
			if ($i == 0) $mItem["qtyDropped"] = 0;
			if ($i == 1) $mItem["qtyDestroyed"] = 0;
			if ($mItem["qtyDropped"] == 0 && $mItem["qtyDestroyed"] == 0) continue;
			$key = buildItemKey($mItem);

			if(!isset($itemList[$key])) {
				$itemList[$key] = $mItem;
				$itemList[$key]["price"] = $mItem["price"] * ($mItem["qtyDropped"] + $mItem["qtyDestroyed"]);
			}
			else {
				$itemList[$key]["qtyDropped"] += $mItem["qtyDropped"];
				$itemList[$key]["qtyDestroyed"] += $mItem["qtyDestroyed"];
				$itemList[$key]["price"] += $mItem["price"] * ($mItem["qtyDropped"] + $mItem["qtyDestroyed"]);
			}
		}
	}
	Memcached::set($md5."combineditems", $itemList);
	return $itemList;
}

function buildItemKey($itm) {
	$key = $itm["typeName"] . ($itm["singleton"] == 2 ? " (Copy)" : "");
	$key .= "|" . ($itm["qtyDropped"] > 0 ? "dropped" : "destroyed");
	if (!isset($itm["flagName"])) $itm["flagName"] = Info::getFlagName($itm["flag"]);
	$key .= "|" . $itm["flagName"];
	if ($itm["groupID"] == 649) $key .= microtime() . rand(0, 10000);
	return $key;
}

function involvedCorpsAndAllis($md5, $involved)
{
	$memcached = Memcached::get($md5."involvedCorpsAndAllis");
	if($memcached) return $memcached;

	$involvedAlliCount = 0;
	$involvedCorpCount = 0;
	// Create the involved corps / alliances list
	$invAll = array();
	foreach($involved as $inv) {
		$allianceID = $inv["allianceID"];
		$corporationID = $inv["corporationID"];
		if (!isset($invAll["$allianceID"])) {
			$involvedAlliCount++;
			$invAll["$allianceID"] = array();
			if ($allianceID != 0) $invAll["$allianceID"]["allianceName"] = $inv["allianceName"];
			if ($allianceID != 0) $invAll["$allianceID"]["name"] = $inv["allianceName"];
			if ($allianceID != 0) $invAll["$allianceID"]["allianceID"] = $allianceID;
			$invAll["$allianceID"]["corporations"] = array();
			$invAll["$allianceID"]["involved"] = 0;
		}
		$involvedCount = $invAll["$allianceID"]["involved"];
		$invAll["$allianceID"]["involved"] = $involvedCount + 1;

		if (!isset($invAll["$allianceID"]["corporations"]["$corporationID"])) {
			$involvedCorpCount++;
			$invAll["$allianceID"]["corporations"]["$corporationID"] = array();
			$invAll["$allianceID"]["corporations"]["$corporationID"]["corporationName"] = isset($inv["corporationName"]) ? $inv["corporationName"] : "";
			$invAll["$allianceID"]["corporations"]["$corporationID"]["name"] = isset($inv["corporationName"]) ? $inv["corporationName"] : "";
			$invAll["$allianceID"]["corporations"]["$corporationID"]["corporationID"] = $corporationID;
			$invAll["$allianceID"]["corporations"]["$corporationID"]["involved"] = 0;
		}
		$involvedCount =  $invAll["$allianceID"]["corporations"]["$corporationID"]["involved"];
		$invAll["$allianceID"]["corporations"]["$corporationID"]["involved"] =  $involvedCount + 1;
	}
	uasort($invAll, "involvedSort");
	foreach($invAll as $id=>$alliance) {
		$corps = $alliance["corporations"];
		uasort($corps, "involvedSort");
		$invAll["$id"]["corporations"] = $corps;
	}
	if ($involvedCorpCount <= 1 && $involvedAlliCount <= 1) $invAll = array();
	Memcached::set($md5."involvedCorpsAndAllis", $invAll);
	return $invAll;
}

function involvedSort($field1, $field2) {
	if ($field1["involved"] == $field2["involved"] && isset($field1["name"]) && isset($field2["name"])) return strcasecmp($field1["name"], $field2["name"]);
	return $field2["involved"] - $field1["involved"];
}

function droppedIsk($md5, $items) {
	$memcached = Memcached::get($md5."droppedisk");
	if($memcached) return $memcached;

	$droppedisk = 0;
	foreach($items as $dropped) $droppedisk += $dropped["price"] * $dropped["qtyDropped"];

	Memcached::set($md5."droppedisk", $droppedisk);
	return $droppedisk;
}
