<?php

/*
 * Each of these echo's is just temporary.  The whole output system will be replaced
 * with either a template system or xml document building.
 *
 * // TODO Don't use echo's
 *
 */


$currentDisplayDate = null;

function displayKill($killID, $killInfo)
{
		global $baseUrl, $pluginUrl, $currentDisplayDate, $p;

		$killDetail = $killInfo['detail'];
		$victim = $killInfo['victim'];
		$attacker = $killInfo['attacker'];

		$killTime = $killDetail['unix_timestamp'];
		$involved = $killDetail['number_involved'] != 0 ? " (" . $killDetail['number_involved'] . ")" : "";
		$displayDate = date("F j, Y", $killTime);
		$displayDateUrl = date("Ymd", $killTime);
		$killTime = date("H:i", $killTime);
		$systemInfo = Info::getSystemInfo($killDetail['solarSystemID']);
		$systemName = $systemInfo['solarSystemName'];
		//$security = $systemInfo['security'];

		if (!isset($currentDisplayDate) || $currentDisplayDate != $displayDate) {
				$currentDisplayDate = $displayDate;
				if (!in_array("date", $p)) echo "\n\t<span class='displayDate'><a href='$baseUrl/date/$displayDateUrl'>$displayDate</a></span>";
		}

		echo "\n\t<span class='smallCorner killPanel'>\n";
		echo "\t\t<span class='victimInfo'>\n";
		displayTypeInfo($killDetail, $victim, false, false);
		echo "\n\t\t</span>\n\t\t<span class='killInfo centerText'>\n";
		echo "\t\t\t<span class='killmailTime'>$killTime $involved</span><span class='killmailSystem'>";
		kblink("system", $systemName, true);
		echo "</span>\n";
		echo "\t\t\t<a href='$baseUrl/killmail/$killID'><span class='killmailLink'>";
		echo "\n\t\t\t\t<img class='wreckImage' height='16' width='16' alt='wreck' src='$pluginUrl/images/wreck.png' title='Detail for killmail $killID'/><br/>Detail</span></a>";
		echo "\n\t\t</span>\n\t\t<span class='attackerInfo'>\n";
		displayTypeInfo($killDetail, $attacker, true, true);
		echo "\n\t\t</span>\n\t</span>\n";
}

function displayTypeInfo($killDetail, $info, $isAttacker = false, $isWith = true)
{
				$charName = Info::getCharName($info['characterID']);
				$corpName = Info::getCorpName($info['corporationID']);
		if ($isAttacker) {
				eveImageLink($info['characterID'] == 0 ? 1 : $info['characterID'], 'pilot', $charName, $info['characterID'] != 0, 64, $isWith);
				eveImageLink($info['shipTypeID'] == 0 ? 1 : $info['shipTypeID'], 'ship', Info::getItemName($info['shipTypeID']), true, 64, $isWith);
				eveImageLink($info['corporationID'] == 0 ? 1 : $info['corporationID'], 'corp', $corpName, true, 64, $isWith);
		} else {
				eveImageLink($info['corporationID'] == 0 ? 1 : $info['corporationID'], 'corp', $corpName, true, 64, $isWith);
				eveImageLink($info['shipTypeID'] == 0 ? 1 : $info['shipTypeID'], 'ship', Info::getItemName($info['shipTypeID']), true, 64, $isWith, "destroyedImage");
				eveImageLink($info['characterID'] == 0 ? 1 : $info['characterID'], 'pilot', $charName, $info['characterID'] != 0, 64, $isWith);
		}
}

$shortHandtoLong = array(
				"corp" => "Corporation",
				"pilot" => "Character",
				"alli" => "Alliance",
				"faction" => "Faction",
				"ship" => "InventoryType",
				"item" => "InventoryType",
				"actualShip" => "Render",
				);

function kblink($short, $name, $isWith = true, $addText = "")
{
		global $baseUrl;

		if ($short == "system" && $addText != "") {
				$classSec = $addText <= 0 ? "nullsec" : ($addText >= 0.5 ? "highsec" : "lowsec");
				$addText = "(<span class='$classSec'>" . number_format($addText, 1) . "</span>)";
		}

		$encoded = urlencode($name);
		$maxLength = 25;
		//if (strlen($name) >= $maxLength) $name = substr($name, 0, $maxLength - 3) . "...";
		$with = $isWith ? "with" : "against";
		echo "<span class='kblink'><a href='$baseUrl/$with/$short/$encoded/'>$name $addText</a></span>";
}

function eveImageLink($id, $shortHand, $altText, $link = true, $size = 64, $isWith = true, $class = "")
{
		global $shortHandtoLong, $baseUrl;

		if ($id == 0) {
				echo "<span class='imageWrapper'>";
				echo "<img width='$size' height='$size' src='http://image.eveonline.com/Icons/items/64_64/icon09_10.png' title='Unknown' alt='Unknown'/>";
				echo "</span>";
				return;
		}
		$long = $shortHandtoLong[$shortHand];
		//if ($long == "Render" && in_array($id, array(588, 596, 601, 670, 606))) $long = "InventoryType";
		$extension = $shortHand == "pilot" ? "jpg" : "png";
		$encoded = urlencode($altText);
		$altText = str_replace("'", "&#39;", $altText);
		$with = $isWith ? "with" : "against";
		if ($link) echo "<a href='$baseUrl/$with/$shortHand/$encoded/'>";

		echo "<span class='imageWrapper'>";
		if ($id != 0) echo "<img width='$size' height='$size' src='http://image.eveonline.com/$long/$id" . "_$size" . ".$extension' title='$altText' alt='$altText'/>";
		else echo "<img width='$size' height='$size' src='http://image.eveonline.com/Icons/items/64_64/icon09_10.png' title='$altText' alt='$altText'/>";
		if ($class != "") echo "<span class='$class'>&nbsp;</span>";
		echo "</span>";
		if ($link) echo "</a>";
}

/**
 *  $kills array an Array of killID's to display
 *  $title The title of the kills
 **/
function showKills($kills, $title, $class = "", $after = null, $before = null)
{
		global $baseUrl, $p;

		$type = strtolower($title);
		echo "<span" . ($class == "" ? " class='searchResults'" : " class='$class' ") . ">";
		$requestURI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
		$requestURI = preg_replace('/after\/[0-9]+/', "", $requestURI);
		$requestURI = preg_replace('/before\/[0-9]+/', "", $requestURI);
		$requestURI = str_replace("losses", "", $requestURI);
		$requestURI = str_replace("kills", "", $requestURI);
		if ($requestURI == "/") $requestURI = "";
		echo "<span class='typeHeader'><a href='" . cleanURL("$baseUrl/$requestURI/$type") . "'>$title</a></span>";
		if (sizeof($kills) == 0) {
				echo "<span class='noKills'>Nothing to display here...</span>";
		} else {
				//$subBaseUrl = implode("/", $p);
				$displayBeforeAfter = !in_array("date", $p) && !in_array("related", $p);
				if ($displayBeforeAfter && $after != null) echo "<span class='typeHeader'><a href='" . cleanURL("$baseUrl/$requestURI/$type/after/$after") . "'>Previous</a></span>";
				foreach ($kills as $killID => $kill) {
						displayKill($killID, $kill);
				}
				if ($displayBeforeAfter && $before != null) echo "<span class='typeHeader'><a href='" . cleanURL("$baseUrl/$requestURI/$type/before/$before") . "'>Earlier</a></span>";
		}
		echo "</span>";
}

function cleanURL($url)
{
		while (strpos($url, "//") !== FALSE) $url = str_replace("//", "/", $url);
		$url = str_replace("http:/", "http://", $url);
		return $url;
}

function displayBigIsk($title, $result, $limit = 5) {
		global $p;

		if (sizeof($result) == 0) return;

		echo "<span class='topDogs bigIskPanel smallCorner'><span class='topDogsTitle'>$title</span><span>";

		foreach ($result as $loss) {
				$name = Info::getCharName($loss["characterID"]);
				$shipName = Info::getItemName($loss["shipTypeID"]);
				$value = $loss["total_price"];
				$killID = $loss["killID"];
				$isk = formatIskPrice($value);
				echo "<span style='width: 125px;'>";
				echo "<a href='/killmail/$killID'>";
				echo "$isk<br/>";
				echo "<span style='padding-left: auto; padding-right: auto; width: 100%;'>";
				eveImageLink($loss["shipTypeID"], "ship", null, false);
				echo "<p>$name</p>";
				echo "</a></span>";
		}
		echo "</span></span>";
}

function displayTopDogs($title, $type, $idName, $topDogs, $limit = 5)
{
		if (!isset($topDogs) || sizeof($topDogs) == 0) return;

		global $p;

		$npcCount = 0;
		foreach ($topDogs as $topDog) {
				$id = $topDog[$idName];
				if ($id == 0) $npcCount++;
		}
		if ($npcCount == sizeof($topDogs)) return;

		echo "<span class='topDogs smallCorner'><span class='topDogsTitle'>$title</span><span>";
		$displayCount = 0;
		foreach ($topDogs as $topDog) {
				$id = $topDog[$idName];
				if ($id == 0) continue;
				$displayCount++;
				if ($displayCount > $limit) continue;
				$name = "";
				switch ($type) {
						case "alli":
								$name = Info::getAlliName($id);
						break;
						case "corp":
								$name = Info::getCorpName($id);
						break;
						case "pilot":
								$name = Info::getCharName($id);
						break;
						case "ship":
								$name = Info::getItemName($id);
						break;
						default: 
						die("unkonwn type: $type");
				}
				$count = isset($topDog['count']) ? $topDog['count'] : null;
				eveImageLink($id, $type, $name, $name != null);
				echo "<br/>";
				kblink($type, $name);
				echo "<br/>";
				if ($count != null) {
						$prefix = in_array("losses", $p) ? " Loss" : " Kill";
						$postfix = in_array("losses", $p) ? "es" : "s";
						echo number_format($count, 0) . $prefix . ($count == 1 ? "" : $postfix) . "<br/><br/>";
				}
		}
		echo "</span></span>";
}

function displayTimeUrl($row)
{
		global $baseUrl;

		if ($row == null) return;
		$year = $row['year'];
		$month = $row['month'];
		$day = isset($row['day']) ? $row['day'] : null;
		if ($day != null) {
				@$text = date("M j, Y", mktime(0, 0, 0, $month, $day, $year, 0));
				if ($month < 10) $month = "0" . $month;
				if ($day < 10) $day = "0" . $day;
				echo "<a href='$baseUrl/date/" . $year . $month . $day . "'>$text</a><br/>";
		} else {
				@$text = date("M, Y", mktime(0, 0, 0, $month, 5, $year, 0));
				if ($month < 10) $month = "0" . $month;
				echo "<a href='$baseUrl/year/$year/month/$month'>$text</a><br/>";
		}
}

function displayPilotDetails(&$killDetail, &$pilotInfo, $isVictim = false)
{
		if ($pilotInfo['characterID'] != 0) eveImageLink($pilotInfo['characterID'], "pilot", Info::getCharName($pilotInfo['characterID']), true, $isVictim
						? 128 : 64);
		if ($pilotInfo['characterID'] != 0 && $pilotInfo['shipTypeID'] != 0) eveImageLink($pilotInfo['shipTypeID'], 'ship', Info::getItemName($pilotInfo['shipTypeID'], true, 64, $isVictim));
		if ($isVictim && $pilotInfo['corporationID'] != 0) eveImageLink($pilotInfo['corporationID'], "corp", Info::getCorpName($pilotInfo['corporationID']));
		if (isset($pilotInfo['weaponTypeID']) && $pilotInfo['weaponTypeID'] != 0) eveImageLink($pilotInfo['weaponTypeID'], "item", Info::getItemName($pilotInfo['weaponTypeID']), false);
		echo "<table class='displayTable'>";
		if ($pilotInfo['characterID'] != 0) displayRow("pilot", "Pilot:", Info::getCharName($pilotInfo['characterID'])); 
		if ($pilotInfo['corporationID'] != 0) displayRow("corp", "Corp:", Info::getCorpName($pilotInfo['corporationID']));
		if ($pilotInfo['allianceID'] != 0) displayRow("alli", "Alliance:", Info::getAlliName($pilotInfo['allianceID']));
		if ($pilotInfo['characterID'] != 0 && $pilotInfo['shipTypeID'] != 0) displayRow("ship", "Ship:", Info::getItemName($pilotInfo['shipTypeID']));
		if ($pilotInfo['characterID'] == 0 && $pilotInfo['shipTypeID'] != 0) echo "<tr><td class='leftDisplayRow'>Weapon:</td><td class='rightDisplayRow'>" . Info::getItemName($pilotInfo['shipTypeID']) . "</td></tr>";
		if ($isVictim) {
				$killTime = $killDetail['unix_timestamp'];
				$displayTime = date("F j, Y H:i", $killTime);
				$systemName = Info::getSystemName($killDetail['solarSystemID']);
				$security = Info::getSystemSecurity($killDetail['solarSystemID']);

				displayRow("system", "System:", $systemName, $security);
				echo "<tr><td class='leftDisplayRow'>Damage:</td><td class='rightDisplayRow'>" . number_format((int)$pilotInfo['damage'], 0) . "</td></tr>";
				echo "<tr><td class='leftDisplayRow'>Time:</td><td class='rightDisplayRow'>$displayTime</td></tr>";
		} else {
				if ($pilotInfo['weaponTypeID'] != 0) echo "<tr><td class='leftDisplayRow'>Weapon:</td><td class='rightDisplayRow'>" . Info::getItemName($pilotInfo['weaponTypeID']) . "</td></tr>";
				echo "<tr><td class='leftDisplayRow'>Inflicted:</td><td class='rightDisplayRow'>" . number_format($pilotInfo['damage']) . "</td></tr>";
		}
		echo "</table>";
}

function displayRow($shortHand, $leftText, $text, $addText = "")
{
		echo "<tr><td class='leftDisplayRow'>$leftText</td><td class='rightDisplayRow'>";
		kblink($shortHand, $text, true, $addText);
		echo "</td></tr>";
}

function infoSpan($value, $image, $url)
{
		global $baseUrl;

		$image = "http://image.eveonline.com" . $image;
		if ($url != null) $url = $baseUrl . $url;
		$max_length = 25;
		//if (strlen($value) >= $max_length) $value = substr($value, 0, $max_length - 3) . "...";
		echo "\t<div class='infoSpan'>";
		if ($url != null) echo "<a href='$url'>";
		echo "<img width='32' height='32' src='$image'/> $value";
		if ($url != null) echo "</a>";
		echo "</div><br/>\n";
}

function displayShip($shipTypeID, &$items)
{
		$shipName = Info::getItemName($shipTypeID);
		echo "\n<table border='1' class='shipDisplay'>";
		echo "\n\t<tr>";
		echo "\n\t\t<td/><td>";
		displayFlagType($items, 0, 12); // High
		echo "\n\t\t</td><td/>";
		echo "\n\t</tr>\n\t<tr>";
		echo "<td  class='verticalImages'>";
		displayFlagType($items, 0, 2663); // Rigs
		displayFlagType($items, 0, 3772); // Subsystems
		echo "</td><td class='actualShipImage'>";
		eveImageLink($shipTypeID, "actualShip", $shipName, false, 256);
		echo "</td><td class='verticalImages'>";
		displayFlagType($items, 0, 13); // Mid
		echo "</td>";
		echo "\n\t</tr>\n\t<tr>";
		echo "<td/><td>";
		displayFlagType($items, 0, 11); // Low
		echo "</td><td/>";
		echo "</tr>";
		echo "</table>";
		//displayFlagType($items, 87, null, false); // Drones
}


$effectToSlot = array(
				"11" => "Low Slots",
				"13" => "Mid Slots",
				"12" => "High Slots",
				"2663" => "Rigs",
				"3772" => "SubSystems",
				"87" => "Drone Bay",
				"5" => "Cargo",
				);

$infernoFlags = array(
				12 => array(27, 34),
				13 => array(19, 26),
				11 => array(11, 18),
				2663 => array(92, 98),
				3772 => array(125, 132),
				);

function displayFlagType(&$items, $flag, $effectID)
{
		global $infernoFlags;

		$infernoKill = false;
		foreach ($items as $item) {
				if ($infernoKill) continue;
				$itemFlag = $item['flag'];
				foreach ($infernoFlags as $flagID=>$values) {
						$infernoKill |= ($itemFlag >= $values[0] && $itemFlag <= $values[1]);
				}
		}

		$toDisplay = array();
		if (!$infernoKill)
				foreach ($items as $item) {
						$itemFlag = $item['flag'];
						if ($flag != $itemFlag) continue;
						$typeID = $item['typeID'];
						$itemEffectID = null;
						if ($effectID != null) $itemEffectID = Info::getEffectID($typeID);
						if ($effectID != $itemEffectID) continue;
						$toDisplay[] = $item;
				}
		else {
				$flags = $infernoFlags[$effectID];
				foreach ($items as $item) {
						$itemFlag = $item['flag'];
						if ($itemFlag >= $flags[0] && $itemFlag <= $flags[1]) {
								$typeID = $item['typeID'];
								$itemEffectID = null;
								if ($effectID != null) $itemEffectID = Info::getEffectID($typeID);
								if ($effectID != $itemEffectID) continue;
								if (!isset($toDisplay[$itemFlag])) $toDisplay[$itemFlag] = $item;
						}
				}
		}

		$totalMods = 0;
		foreach ($toDisplay as $item) {
				$typeID = $item['typeID'];
				//$itemFlag = $item['flag'];
				$typeName = Info::getItemName($typeID);
				$dropped = $item['qtyDropped'];
				$destroyed = $item['qtyDestroyed'];
				$total = $dropped + $destroyed;
				$totalMods += $total;
				for ($i = 0; $i < $total; $i++) {
						eveImageLink($typeID, "item", $typeName, false, 32);
				}
		}
}

function mapItemFlags($items)
{
		$flags = array();
		foreach ($items as $item) {
				$flagType = $item['flag'];
				if (!isset($flags["$flagType"])) $flags["$flagType"] = array();
				$flags["$flagType"][] = $item;
		}
		return $flags;
}

$formatIskIndexes = array("", "K", "M", "B", "T", "TT", "TTT");
function formatIskPrice($value)
{
		global $formatIskIndexes;

		if ($value == 0) return "0.00";
		if ($value < 1000) return number_format($value, 2);
		$iskIndex = 0;
		while ($value > 999.99) {
				$value /= 1000;
				$iskIndex++;
		}
		return number_format($value, 2) . $formatIskIndexes[$iskIndex];
}

function pluralize($count, $text)
{
		if ($count == 1) return "$count $text";
		return number_format($count, 0) . " {$text}s";
}

function displayStats($stats = array())
{
		if ($stats == null || sizeof($stats) == 0) return;

		$totalKills = 0;
		$totalLosses = 0;
		$totalKillVal = 0;
		$totalLossVal = 0;
		$shipTypes = array();
		foreach ($stats as $stat) {
				$groupID = $stat["groupID"];
				if ($groupID == null) continue;
				$groupName = $groupID == 0 ? "Unknown" : Info::getGroupName($groupID);
				$groupName = trim(str_replace("Ship", "", $groupName));
				$shipTypes[$groupName] = $stat;
				$totalKills += getValue($stat, "kills_num"); //$stat["kills_num"];
				$totalLosses += getValue($stat, "losses_num"); //$stat["losses_num"];
				$totalKillVal += getValue($stat, "kills_value"); // $stat["kills_value"];
				$totalLossVal += getValue($stat, "losses_value"); //$stat["losses_value"];
		}

		echo "<fieldset class='smallCorner statistics'><legend class='bold'>Statistics</legend>";
		ksort($shipTypes);
		echo "<span class='groupStat bold groupStatTitle'>";
		echo "<span class='groupName'>ShipType</span>";
		echo "<span class='groupCount'>Kills</span>";
		echo "<span class='groupPct'> % </span>";
		echo "<span class='groupValue'>Value</span>";
		echo "<span class='groupCount'>Losses</span>";
		echo "<span class='groupPct'> % </span>";
		echo "<span class='groupValue'>Value</span>";
		echo "</span>";
		$rowCount = 1;
		foreach ($shipTypes as $shipType => $stat) {
				$kills = getValue($stat, "kills_num"); //$stat['kills_num'];
				$losses = getValue($stat, "losses_num"); //$stat['losses_num'];
				if ($kills == 0 && $losses == 0) continue;
				$kills_value = getValue($stat, "kills_value"); //$stat['kills_value'];
				$losses_value = getValue($stat, "losses_value"); // $stat['losses_value'];
				$rowCount++;
				$oddRow = $rowCount % 2 == 0 ? "" : " oddRow";
				echo "<span class='groupStat $oddRow'>";
				echo "<span class='groupName'>$shipType</span>";
				echo "<span class='groupCount'>" . number_format($kills, 0) . "</span>";
				echo "<span class='groupPct'>" . calcPercentage($kills, $totalKills) . "</span>";
				echo "<span class='groupValue'>" . formatIskPrice($kills_value) . "</span>";
				echo "<span class='groupCount'>" . number_format($losses, 0) . "</span>";
				echo "<span class='groupPct'>" . calcPercentage($losses, $totalLosses) . "</span>";
				echo "<span class='groupValue'>" . formatIskPrice($losses_value) . "</span>";
				echo "</span>";
		}
		echo "<span class='groupStat bold groupStatTotal'>";
		echo "<span class='groupName'>Total</span>";
		echo "<span class='groupCount'>" . number_format($totalKills, 0) . "</span>";
		echo "<span class='groupPct'>&nbsp;</span>";
		echo "<span class='groupValue'>" . formatIskPrice($totalKillVal) . "</span>";
		echo "<span class='groupCount'>" . number_format($totalLosses, 0) . "</span>";
		echo "<span class='groupPct'>&nbsp;</span>";
		echo "<span class='groupValue'>" . formatIskPrice($totalLossVal) . "</span>";
		echo "</span>";
		echo "</fieldset>";
}

function getValue(&$stat, $field) {
		if (!isset($stat[$field])) return 0;
		return $stat[$field];
}

function calcPercentage($numerator, $denominator)
{
		if ($denominator == 0) return "0.0 %";
		return number_format(($numerator / $denominator) * 100, 1) . " %";
}
