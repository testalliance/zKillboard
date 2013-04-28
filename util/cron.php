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

// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";

function handleApiException($keyID, $charID, $exception)
{
	$code = $exception->getCode();
	$message = $exception->getMessage();
	$clearCharacter = false;
	$clearAllCharacters = false;
	$clearApiEntry = false;
	$updateCacheTime = false;
	$demoteCharacter = false;
	$cacheUntil = 0;
	switch ($code) {
		case 904:
			$msg = "Error 904 detected using key $keyID";
			Log::log($msg);
			$msg = "|r|$msg";
//			Log::irc($msg);
//			Log::admin($msg);
			break;
		case 403:
		case 502:
		case 503: // Service Unavailable - try again later
			$cacheUntil = time() + 300;
			$updateCacheTime = true;
			break;
		case 119: // Kills exhausted: retry after [{0}]
			$cacheUntil = $exception->cached_until;
			$updateCacheTime = true;
			break;
		case 120: // Expected beforeKillID [{0}] but supplied [{1}]: kills previously loaded.
			$cacheUntil = $exception->cached_until;
			$updateCacheTime = true;
			break;
		case 221: // Demote toon, illegal page access
			$clearAllCharacters = true;
			$clearApiEntry = true;
			break;
		case 220:
		case 200: // Current security level not high enough.
			// Typically happens when a key isn't a full API Key
			$clearAllCharacters = true;
			$clearApiEntry = true;
			//$code = 203; // Force it to go away, no point in keeping this key
			break;
		case 522:
		case 201: // Character does not belong to account.
			// Typically caused by a character transfer
			$clearCharacter = true;
			break;
		case 207: // Not available for NPC corporations.
		case 209:
			$demoteCharacter = true;
			break;
		case 222:
		case 403:
		case 211: // Login denied by account status
			// Remove characters, will revalidate with next doPopulate
			$clearAllCharacters = true;
			$clearApiEntry = true;
			break;
		case 202: // API key authentication failure.
		case 203: // Authentication failure - API is no good and will never be good again
		case 204: // Authentication failure.
		case 205: // Authentication failure (final pass).
		case 210: // Authentication failure.
		case 521: // Invalid username and/or password passed to UserData.LoginWebUser().
			$clearAllCharacters = true;
			$clearApiEntry = true;
			break;
		case 500: // Internal Server Error (More CCP Issues)
		case 520: // Unexpected failure accessing database. (More CCP issues)
		case 404: // URL Not Found (CCP having issues...)
		case 902: // Eve backend database temporarily disabled
			$updateCacheTime = true;
			$cacheUntil = time() + 3600; // Try again in an hour...
			break;
		case 0: // API Date could not be read / parsed, original exception (Something is wrong with the XML and it couldn't be parsed)
		default: // try again in 5 minutes
			Log::log("$keyID - Unhandled error - Code $code - $message");
			//$updateCacheTime = true;
			$clearApiEntry = true;
			//$cacheUntil = time() + 300;
	}

	if ($demoteCharacter && $charID != 0) {
		if (false === Db::execute("update zz_api_characters set isDirector = 'F' where characterID = :charID", array(":charID" => $charID), false)) {
			$clearCharacter = true;
		}
	}

	if ($clearCharacter && $charID != 0) {
		Db::execute("delete from zz_api_characters where keyID = :keyID and characterID = :charID", array(":keyID" => $keyID, ":charID" => $charID));
	}

	if ($clearAllCharacters) {
		Db::execute("delete from zz_api_characters where keyID = :keyID", array(":keyID" => $keyID));
	}

	if ($clearApiEntry) {
		Db::execute("update zz_api set errorCode = :code where keyID = :keyID", array(":keyID" => $keyID, ":code" => $code));
	}

	if ($updateCacheTime && $cacheUntil != 0 && $charID != 0) {
		Db::execute("update zz_api_characters set cachedUntil = :cacheUntil where characterID = :charID",
				array(":cacheUntil" => $cacheUntil, ":charID" => $charID));
	}
	Db::execute("update zz_api_characters set errorCode = :code where keyID = :keyID and characterID = :charID", array(":keyID" => $keyID, ":charID" => $charID, ":code" => $code));
}

function maintenanceMode() {
	return "true" == Db::queryField("select contents from zz_storage where locker = 'maintenance'", "contents", array(), 0);
}

/**
 * Processes unprocessed kills
 *
 *
 * @return void
 */
function parseKills()
{
	if (maintenanceMode()) return;

	$timer = new Timer();

	$maxTime = 65 * 1000 ;

	Db::execute("create table if not exists zz_items_temporary select * from zz_items where 1 = 0");
	Db::execute("create table if not exists zz_participants_temporary select * from zz_participants where 1 = 0");

	$numKills = 0;

	while ($timer->stop() < $maxTime) {
		if (maintenanceMode()) {
			removeTempTables();
			return;
		}
		Db::execute("delete from zz_items_temporary");
		Db::execute("delete from zz_participants_temporary");

		//Log::log("Fetching kills for processing...");
		$result = Db::query("select * from zz_killmails where processed = 0 order by killID desc limit 100", array(), 0);

		if (sizeof($result) == 0) {
			$currentSecond = (int) date("s");
			$sleepTime = max(1, 15 - ($currentSecond % 15));
			sleep($sleepTime);
			continue;
		}

		//Log::log("Processing fetched kills...");
		$processedKills = array();
		$cleanupKills = array();
		foreach ($result as $row) {
			$numKills++;
			$kill = json_decode($row['kill_json']);
			if (!isset($kill->killID)) {
				Log::log("Problem with kill " . $row["killID"]);
				Db::execute("update zz_killmails set processed = 2 where killid = :killid", array(":killid" => $row["killID"]));
				continue;
			}
			$killID = $kill->killID;

			$date = $kill->killTime;

			$date = strtotime($date);
			$year = date("Y", $date);
			$month = date("m", $date);
			$week = date("W", $date);
			if ($week >= 52 && $month == 1) $year -= 1;
			if (strlen($week) < 2) $week = "0$week";

			// Cleanup if we're reparsing
			$cleanupKills[] = $killID;

			// Do some validation on the kill
			if (!validKill($kill)) {
				Db::execute("update zz_killmails set processed = 3 where killid = :killid", array(":killid" => $row["killID"]));
				//processVictim($year, $month, $week, $kill, $killID, $kill->victim, true);
				continue;
			}

			$totalCost = 0;
			$itemInsertOrder = 0;

			$totalCost += processItems($year, $week, $kill, $killID, $kill->items, $itemInsertOrder);
			$totalCost += processVictim($year, $month, $week, $kill, $killID, $kill->victim, false);
			foreach ($kill->attackers as $attacker) processAttacker($year, $month, $week, $kill, $killID, $attacker, $kill->victim->shipTypeID, $totalCost);
			$points = Points::calculatePoints($killID, true);
			Db::execute("update zz_participants_temporary set points = :points, number_involved = :numI, total_price = :tp where killID = :killID", array(":killID" => $killID, ":points" => $points, ":numI" => sizeof($kill->attackers), ":tp" => $totalCost));

			$processedKills[] = $killID;
		}
		while (Db::queryField("show session status like 'Not_flushed_delayed_rows'", "Value", array(), 0) > 0) usleep(50000);
		if (sizeof($cleanupKills)) {
			Db::execute("delete from zz_items where killID in (" . implode(",", $cleanupKills) . ")");
			Db::execute("delete from zz_participants where killID in (" . implode(",", $cleanupKills) . ")");
		}
		Db::execute("insert into zz_items select * from zz_items_temporary");
		Db::execute("insert into zz_participants select * from zz_participants_temporary");
		if (sizeof($processedKills)) Db::execute("update zz_killmails set processed = 1 where killID in (" . implode(",", $processedKills) . ")");
		foreach($processedKills as $killID) {
			Stats::calcStats($killID, true);
		}
	}
	if ($numKills > 0) Log::log("Processed $numKills kills");
	removeTempTables();
}

function removeTempTables() {
	//Db::execute("drop table if exists zz_participants_temporary");
	//Db::execute("drop table if exists zz_items_temporary");
}

function fetchApis()
{
	$fetchesPerSecond = 30;

	$timer = new Timer();

	$preFetched = array();

	$maxTime = 60 * 1000;
	while ($timer->stop() < $maxTime) {
		Db::execute("delete from zz_api_characters where isDirector = ''");

		$allChars = Db::query("select apiRowID, cachedUntil from zz_api_characters where errorCode != 120 and cachedUntil < date_sub(now(), interval 30 second) order by cachedUntil, keyID, characterID limit 1000", array(), 0);

		$total = sizeof($allChars);
		$corpsToSkip = array();
		$iterationCount = 0;

		if ($total == 0) sleep(1);
		else foreach ($allChars as $char) {
			if ($timer->stop() > $maxTime) return;

			$apiRowID = $char["apiRowID"];
			$cachedUntil = $char["cachedUntil"];

			Db::execute("update zz_api_characters set cachedUntil = date_add(if(cachedUntil=0, now(), cachedUntil), interval 5 minute), lastChecked = now() where apiRowID = :id", array(":id" => $apiRowID));

			$m = $iterationCount % $fetchesPerSecond;
			$command = "flock -w 60 /tmp/locks/preFetch.$m php5 " . dirname(__FILE__) . "/fetchKillLog.php $apiRowID";
			$command = escapeshellcmd($command);
			exec("$command >/dev/null 2>/dev/null &");

			$iterationCount++;
			if ($m == 0) { sleep(1); }
		}
	}
}

function doApiSummary()
{
	$lastActualKills = Db::queryField("select contents count from zz_storage where locker = 'actualKills'", "count", array(), 0);
	$actualKills = Db::queryField("select count(*) count from zz_killmails where processed = 1", "count", array(), 0);

	$lastTotalKills = Db::queryField("select contents count from zz_storage where locker = 'totalKills'", "count", array(), 0);
	$totalKills = Db::queryField("select count(*) count from zz_killmails", "count", array(), 0);

	Db::execute("replace into zz_storage (locker, contents) values ('totalKills', $totalKills)");
	Db::execute("replace into zz_storage (locker, contents) values ('actualKills', $actualKills)");
	Db::execute("delete from zz_storage where locker like '%KillsProcessed'");

	$actualDifference = number_format($actualKills - $lastActualKills, 0);
	$totalDifference = number_format($totalKills - $lastTotalKills, 0);

	Log::irc("|g|$actualDifference|n| mails processed | |g|$totalDifference|n| kills added");
}

function doPopulateCharactersTable()
{
	$timer = new Timer();
	$maxTime = 65 * 1000;

	$fetchesPerSecond = 25;
	$iterationCount = 0;

	while ($timer->stop() < $maxTime) {
		$keyIDs = Db::query("select distinct keyID from zz_api where errorCode not in (203, 220) and lastValidation < date_sub(now(), interval 2 hour)
				order by lastValidation, dateAdded desc limit 100", array(), 0);

		if (sizeof($keyIDs) == 0) sleep(1);
		else foreach($keyIDs as $row) {
			$keyID = $row["keyID"];
			$m = $iterationCount % $fetchesPerSecond;
			Db::execute("update zz_api set lastValidation = date_add(lastValidation, interval 5 minute) where keyID = :keyID", array(":keyID" => $keyID));
			$command = "flock -w 60 /tmp/locks/preFetchChars.$m php5 " . dirname(__FILE__) . "/fetchCharacters.php $keyID";
			$command = escapeshellcmd($command);
			//Log::log($command);
			exec("$command >/dev/null 2>/dev/null &");
			$iterationCount++;
			if ($iterationCount % $fetchesPerSecond == 0) sleep(1);
		}
	}
}

function processRawApi($keyID, $charID, $killlog) {
	$count = 0;
	$maxKillID = Db::queryField("select maxKillID from zz_api_characters where keyID = :keyID and characterID = :charID", "maxKillID",
			array(":keyID" => $keyID, ":charID" => $charID), 0);
	if ($maxKillID === null) $maxKillID = 0;
	$insertedMaxKillID = $maxKillID;
	foreach ($killlog->kills as $kill) {
		$killID = $kill->killID;
		//if ($killID < $maxKillID) continue;
		$insertedMaxKillID = max($insertedMaxKillID, $killID);

		$json = json_encode($kill->toArray());
		$hash = Util::getKillHash(null, $kill);
		$mKillID = Db::queryField("select killID from zz_killmails where killID < 0 and processed = 1 and hash = :hash", "killID", array(":hash" => $hash), 0);
		if ($mKillID) cleanDupe($mKillID, $killID);
		$added = Db::execute("insert ignore into zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
				array(":killID" => $killID, ":hash" => $hash, ":source" => "keyID:$keyID", ":json" => $json));
		$count += $added;
	}
	if ($maxKillID != $insertedMaxKillID) {
		Db::execute("insert into zz_api_characters (keyID, characterID, maxKillID) values (:keyID, :charID, :maxKillID)
				on duplicate key update maxKillID = :maxKillID",
				array(":keyID" => $keyID, ":charID" => $charID, "maxKillID" => $insertedMaxKillID));
	}
	return $count;
}

/**
 * @param  $kill
 * @return bool
 */
function validKill(&$kill)
{
	$killID = $kill->killID;
	$victimCorp = $kill->victim->corporationID < 1000999 ? 0 : $kill->victim->corporationID;
	$victimAlli = $kill->victim->allianceID;

	$npcOnly = true;
	$blueOnBlue = true;
	foreach ($kill->attackers as $attacker) {
		$attackerGroupID = Info::getGroupID($attacker->shipTypeID);
		if ($attackerGroupID == 365) return true; // A tower is involved

		// Don't process the kill if it's NPC only
		$npcOnly &= $attacker->characterID == 0 && $attacker->corporationID < 1999999;

		// Check for blue on blue
		if ($attacker->characterID != 0) $blueOnBlue &= $victimCorp == $attacker->corporationID && $victimAlli == $attacker->allianceID;
	}
	if ($npcOnly /*|| $blueOnBlue*/) return false;

	return true;
}

function processVictim(&$year, &$month, &$week, &$kill, $killID, &$victim, $isNpcVictim)
{
	$shipPrice = getPrice($victim->shipTypeID);
	$groupID = Info::getGroupID($victim->shipTypeID);
	$regionID = Info::getRegionIDFromSystemID($kill->solarSystemID);

	$dttm = (string) $kill->killTime;

	if (!$isNpcVictim) Db::execute("
			insert into zz_participants_temporary
			(killID, solarSystemID, regionID, isVictim, shipTypeID, groupID, shipPrice, damage, factionID, allianceID,
			 corporationID, characterID, dttm, vGroupID)
			values
			(:killID, :solarSystemID, :regionID, 1, :shipTypeID, :groupID, :shipPrice, :damageTaken, :factionID, :allianceID,
			 :corporationID, :characterID, :dttm, :vGroupID)",
			(array(
				   ":killID" => $killID,
				   ":solarSystemID" => $kill->solarSystemID,
				   ":regionID" => $regionID,
				   ":shipTypeID" => $victim->shipTypeID,
				   ":groupID" => $groupID,
				   ":vGroupID" => $groupID,
				   ":shipPrice" => $shipPrice,
				   ":damageTaken" => $victim->damageTaken,
				   ":factionID" => $victim->factionID,
				   ":allianceID" => $victim->allianceID,
				   ":corporationID" => $victim->corporationID,
				   ":characterID" => $victim->characterID,
				   ":dttm" => $dttm,
				  )));

	Info::addChar($victim->characterID, $victim->characterName);
	Info::addCorp($victim->corporationID, $victim->corporationName);
	Info::addAlli($victim->allianceID, $victim->allianceName);

	return $shipPrice;
}

function processAttacker(&$year, &$month, &$week, &$kill, &$killID, &$attacker, $victimShipTypeID, $totalCost)
{
	$victimGroupID = Info::getGroupID($victimShipTypeID);
	$attackerGroupID = Info::getGroupID($attacker->shipTypeID);
	$regionID = Info::getRegionIDFromSystemID($kill->solarSystemID);

	$dttm = (string) $kill->killTime;

	Db::execute("
			insert into zz_participants_temporary
			(killID, solarSystemID, regionID, isVictim, characterID, corporationID, allianceID, total_price, vGroupID,
			 factionID, damage, finalBlow, weaponTypeID, shipTypeID, groupID, dttm)
			values
			(:killID, :solarSystemID, :regionID, 0, :characterID, :corporationID, :allianceID, :total, :vGroupID,
			 :factionID, :damageDone, :finalBlow, :weaponTypeID, :shipTypeID, :groupID, :dttm)",
			(array(
				   ":killID" => $killID,
				   ":solarSystemID" => $kill->solarSystemID,
				   ":regionID" => $regionID,
				   ":characterID" => $attacker->characterID,
				   ":corporationID" => $attacker->corporationID,
				   ":allianceID" => $attacker->allianceID,
				   ":factionID" => $attacker->factionID,
				   ":damageDone" => $attacker->damageDone,
				   ":finalBlow" => $attacker->finalBlow,
				   ":weaponTypeID" => $attacker->weaponTypeID,
				   ":shipTypeID" => $attacker->shipTypeID,
				   ":groupID" => $attackerGroupID,
				   ":dttm" => $dttm,
				   ":total" => $totalCost,
				   ":vGroupID" => $victimGroupID,
				  )));
	Info::addChar($attacker->characterID, $attacker->characterName);
	Info::addCorp($attacker->corporationID, $attacker->corporationName);
	Info::addAlli($attacker->allianceID, $attacker->allianceName);
}

function processItems(&$year, &$week, &$kill, &$killID, &$items, &$itemInsertOrder, $isCargo = false, $parentFlag = 0) {
	$totalCost = 0;
	foreach ($items as $item) {
		$totalCost += processItem($year, $week, $kill, $killID, $item, $itemInsertOrder++, $isCargo, $parentFlag);
		if (@is_array($item->items)) {
			$itemContainerFlag = $item->flag;
			$totalCost += processItems($year, $week, $kill, $killID, $item->items, $itemInsertOrder, true, $itemContainerFlag);
		}
	}
	return $totalCost;
}

$itemNames = null;
$itemPrices = null;

function getPrice($typeID) {
	global $itemPrices;
	if ($itemPrices == null) {
		$itemPrices = array();
		$results = Db::query("select typeID, price from zz_prices", array(), 0);
		foreach ($results as $row) {
			$itemPrices[$row["typeID"]] = $row["price"];
		}
	}
	$price = isset($itemPrices[$typeID]) ? $itemPrices[$typeID] : null;
	if ($price === null || $price == 0 ) $price = Price::getItemPrice($typeID);

	return $price;
}

function processItem(&$year, &$week, &$kill, &$killID, &$item, $itemInsertOrder, $isCargo = false, $parentContainerFlag = -1)
{
	global $itemNames;
	if ($itemNames == null ) {
		$itemNames = array();
		$results = Db::query("select typeID, typeName from ccp_invTypes", array(), 3600);
		foreach ($results as $row) {
			$itemNames[$row["typeID"]] = $row["typeName"];
		}
	}
	$typeID = $item->typeID;
	$itemName = $itemNames[$item->typeID];

	$price = getPrice($typeID);
	if ($isCargo && strpos($itemName, "Blueprint") !== false) $item->singleton = 2;
	if ($item->singleton == 2) {
		$price = $price / 100;
	}

	Db::execute("
			insert into zz_items_temporary
			(killID, typeID, flag, qtyDropped, qtyDestroyed, insertOrder, price, singleton, year, week, inContainer)
			values
			(:killID, :typeID, :flag, :qtyDropped, :qtyDestroyed, :insertOrder, :price, :singleton, :year, :week, :inContainer)",
			(array(
				   ":killID" => $killID,
				   ":typeID" => $item->typeID,
				   ":flag" => ($isCargo ? $parentContainerFlag : $item->flag),
				   ":qtyDropped" => $item->qtyDropped,
				   ":qtyDestroyed" => $item->qtyDestroyed,
				   ":insertOrder" => $itemInsertOrder,
				   ":price" => $price,
				   ":singleton" => $item->singleton,
				   ":year" => $year,
				   ":week" => $week,
				   ":inContainer" => ($isCargo ? 1 : 0),
				  )));

	return ($price * ($item->qtyDropped + $item->qtyDestroyed));
}

function populateAllianceList()
{
	Log::log("Repopulating alliance tables.");

	$allianceCount = 0;
	$corporationCount = 0;

	$pheal = Util::getPheal();
	$pheal->scope = "eve";
	$list = null;
	$exception = null;
	try {
		$list = $pheal->AllianceList();
	} catch (Exception $ex) {
		$exception = $ex;
	}
	if ($list != null && sizeof($list->alliances) > 0) {
		Db::execute("update zz_alliances set memberCount = 0");
		Db::execute("update zz_corporations set allianceID = 0");
		foreach ($list->alliances as $alliance) {
			$allianceCount++;
			$allianceID = $alliance['allianceID'];
			$shortName = $alliance['shortName'];
			$name = $alliance['name'];
			$executorCorpID = $alliance['executorCorpID'];
			$memberCount = $alliance['memberCount'];
			$parameters = array(":alliID" => $allianceID, ":shortName" => $shortName, ":name" => $name,
					":execID" => $executorCorpID, ":memberCount" => $memberCount);
			Db::execute("insert into zz_alliances (allianceID, ticker, name, executorCorpID, memberCount, lastUpdated) values
					(:alliID, :shortName, :name, :execID, :memberCount, now())
					on duplicate key update memberCount = :memberCount, ticker = :shortName, name = :name,
					executorCorpID = :execID, lastUpdated = now()", $parameters);
			$corporationCount += sizeof($alliance->memberCorporations);
			foreach($alliance->memberCorporations as $corp) {
				$corpID = $corp->corporationID;
				Db::execute("update zz_corporations set allianceID = :alliID where corporationID = :corpID",
						array(":alliID" => $allianceID, ":corpID" => $corpID));
			}
		}

		$allianceCount = number_format($allianceCount, 0);
		$corporationCount = number_format($corporationCount, 0);
		Log::log("Alliance tables repopulated - $allianceCount active Alliances with a total of $corporationCount Corporations");
	} else {
		Log::log("Unable to pull Alliance XML from API.  Will try again later.");
		if ($exception != null) throw $exception;
		throw new Exception("Unable to pull Alliance XML from API.  Will try again later");
	}
}

function minutely() {
	$killsLastHour = Db::queryField("select count(*) count from zz_killmails where insertTime > date_sub(now(), interval 1 hour)", "count");
	Storage::store("KillsLastHour", $killsLastHour);
	Domains::deleteDomainsFromCloudflare();
	Domains::registerDomainsWithCloudflare();
}

function hourly() {
	$percentage = Storage::retrieve("LastHourPercentage", 10);
	$row = Db::queryRow("select sum(if(errorCode = 0, 1, 0)) good, sum(if(errorCode != 0, 1, 0)) bad from zz_api_characters");
	$good = $row["good"];
	$bad = $row["bad"];
	if ($bad > (($bad + $good) * ($percentage / 100))) {
		if($percentage > 15)
			Log::irc("|r|API gone haywire?  Over $percentage% of API's reporting an error atm.");
		$percentage += 5;
	} else if ($bad < (($bad + $good) * (($percentage - 5) / 100))) $percentage -= 5;
	if ($percentage < 10) $percentage = 10;
	Storage::store("LastHourPercentage", $percentage);

	Db::execute("delete from zz_api_log where requestTime < date_sub(now(), interval 36 hour)");

	// Cleanout manual mail stuff where the manual mail has been api verified
	Db::execute("update zz_killmails set kill_json = '' where processed = 2 and killID < 0 and kill_json != ''");
	Db::execute("update zz_manual_mails set rawText = '' where killID > 0 and rawText != ''");
}

function social() {
	Social::findConversations();
}

function fightFinder() {
	Db::execute("delete from zz_social where insertTime < date_sub(now(), interval 23 hour)");
	$minPilots = 100;
	$minWrecks = 100;
	$result = Db::query("select * from (select solarSystemID, count(distinct characterID) count, count(distinct killID) kills from zz_participants where characterID != 0 and killID > 0 and dttm > date_sub(now(), interval 1 hour) group by 1 order by 2 desc) f where count >= $minPilots and kills > $minWrecks");
	foreach($result as $row) {
		$systemID = $row["solarSystemID"];
		$key = ($row["solarSystemID"] * 100) + date("H");
		$key2 = ($row["solarSystemID"] * 100) + date("H", time() + 3600);

		$count = Db::queryField("select count(*) count from zz_social where killID = :killID", "count", array(":killID" => $key), 0);
		if ($count != 0) continue;
		Db::execute("insert ignore into zz_social (killID) values (:k1), (:k2)", array(":k1" => $key, ":k2" => $key2));

		Info::addInfo($row);
		$wrecks = number_format($row['kills'], 0);
		$involved = number_format($row['count'], 0);
		$system = $row["solarSystemName"];
		$date = date("YmdH00");
		$link = "https://zkillboard.com/related/$systemID/$date/";

		$message = "Battle detected in |g|$system|n| with |g|$involved|n| involved and |g|$wrecks|n| wrecks. |g|$link";
		Log::irc($message);
		// let this run for a few days to ensure it works correctly, then TODO make it twitter the msg too
		//$message = Log::stripIRCColors($message);
	}
}

function cleanDupe($mKillID, $killID) {
	Db::execute("update zz_killmails set processed = 2 where killID = :mKillID", array(":mKillID" => $mKillID));
	Db::execute("update zz_manual_mails set killID = :killID where mKillID = :mKillID",
			array(":killID" => $killID, ":mKillID" => (-1 * $mKillID)));
	Stats::calcStats($mKillID, false); // remove manual version from stats
}

function updateCharacters() {
	$minute = (int) date("i");
	if ($minute == 0) {
		Db::execute("insert ignore into zz_characters (characterID) select ceoID from zz_corporations");
		Db::execute("insert ignore into zz_characters (characterID) select characterID from zz_api_characters where characterID != 0");
	}
	Db::execute("delete from zz_characters where characterID < 9000000");
	Db::execute("update zz_characters set lastUpdated = now() where characterID >= 30000000 and characterID <= 31004590");
	Db::execute("update zz_characters set lastUpdated = now() where characterID >= 40000000 and characterID <= 41004590");
	$result = Db::query("select characterID, name from zz_characters where lastUpdated < date_sub(now(), interval 7 day) and corporationID != 1000001 order by lastUpdated limit 600", array(), 0);
	foreach ($result as $row) {
		$id = $row["characterID"];
		$oName = $row["name"];
		//Db::execute("update zz_characters set lastUpdated = now() where characterID = :id", array(":id" => $id));

		/*$pheal = Util::getPheal();
		  $pheal->scope = "eve";
		  try {
		  $charInfo = $pheal->CharacterInfo(array("characterid" => $id));
		  $name = $charInfo->characterName;
		  $corpID = $charInfo->corporationID;
		  $alliID = $charInfo->allianceID;
		//echo "$name $id $corpID $alliID\n";
		Db::execute("update zz_characters set name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" 
		=> $name, ":corpID" => $corpID, ":alliID" => $alliID));
		} catch (Exception $ex) {
		// Is this name even a participant?
		$count = Db::queryField("select count(*) count from zz_participants where characterID = :id", "count", array(":id" => $id));
		if ($count == 0) {
		Db::execute("delete from zz_characters where characterID = :id", array(":id" => $id));
		}
		else if ($ex->getCode() != 503) Log::log("ERROR Validating Character $id" . $ex->getMessage());
		}*/
		$json = file_get_contents("http://evewho.com/ek_pilot_id.php?id=$id");
		$info = json_decode($json, true);
		Db::execute("update zz_characters set lastUpdated = now(), name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" => $info["name"], ":corpID" => $info["corporation_id"], ":alliID" => $info["alliance_id"]));
		//usleep(100000);
	}
}

function updateCorporations() {
	Db::execute("delete from zz_corporations where corporationID = 0");
	Db::execute("insert ignore into zz_corporations (corporationID) select executorCorpID from zz_alliances where executorCorpID > 0");
	$result = Db::query("select corporationID, name, memberCount, ticker from zz_corporations where (memberCount is null or memberCount > 0 or lastUpdated = 0)  and corporationID >= 1000001 order by lastUpdated limit 100", array(), 0);
	foreach($result as $row) {
		$id = $row["corporationID"];
		$oMemberCount = $row["memberCount"];
		$oName = $row["name"];
		$oTicker = $row["ticker"];

		//echo "$id $oName\n";

		$pheal = Util::getPheal();
		$pheal->scope = "corp";
		try {
			$corpInfo = $pheal->CorporationSheet(array("corporationID" => $id));
			$name = $corpInfo->corporationName;
			$ticker = $corpInfo->ticker;
			$memberCount = $corpInfo->memberCount;
			$ceoID = $corpInfo->ceoID;
			if ($ceoID == 1) $ceoID = 0;
			$dscr = $corpInfo->description;

			Db::execute("update zz_corporations set name = :name, ticker = :ticker, memberCount = :memberCount, ceoID = :ceoID, description = :dscr, lastUpdated = now() where corporationID = :id",
					array(":id" => $id, ":name" => $name, ":ticker" => $ticker, ":memberCount" => $memberCount, ":ceoID" => $ceoID, ":dscr" => $dscr));

		} catch (Exception $ex) {
			print_r($ex);
			Db::execute("update zz_corporations set lastUpdated = now() where corporationID = :id", array(":id" => $id));
			if ($ex->getCode() != 503) Log::log("ERROR Validating Corp $id: " . $ex->getMessage());
		}
		usleep(100000);
	}
}
