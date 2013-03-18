<?php

// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/pheal/config.php";

function handleApiException($user_id, $char_id, $exception)
{
		global $dbPrefix;

		$code = $exception->getCode();
		$message = $exception->getMessage();
		$clearCharacter = false;
		$clearAllCharacters = false;
		$clearApiEntry = false;
		$updateCacheTime = false;
		$demoteCharacter = false;
		$cacheUntil = 0;
		switch ($code) {
				case 503: // Service Unavailable - try again later
						$cacheUntil = time() + 3600;
						$updateCacheTime = true;
						break;
				case 119: // Kills exhausted: retry after [{0}]
						$cacheUntil = $exception->cached_until_unixtime + (24 * 60 * 60);
						$updateCacheTime = true;
						break;
				case 120: // Expected beforeKillID [{0}] but supplied [{1}]: kills previously loaded.
						$cacheUntil = $exception->cached_until_unixtime + 30;
						$updateCacheTime = true;
						break;
				case 221:
				case 220:
				case 200: // Current security level not high enough.
						// Typically happens when a key isn't a full API Key
						$clearAllCharacters = true;
						$clearApiEntry = true;
						$code = 203; // Force it to go away, no point in keeping this key
						break;
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
				case 0: // API Date could not be read / parsed, original exception (Something is wrong with the XML and it couldn't be parsed)
				case 500: // Internal Server Error (More CCP Issues)
				case 520: // Unexpected failure accessing database. (More CCP issues)
				case 404: // URL Not Found (CCP having issues...)
				case 902: // Eve backend database temporarily disabled
						$updateCacheTime = true;
						$cacheUntil = time() + 3600; // Try again in an hour...
						break;
				default:
						echo "Unhandled error - Code $code - $message\n";
						$updateCacheTime = true;
						$clearApiEntry = true;
						$cacheUntil = time() + 3600;
		}

		if ($demoteCharacter && $char_id != 0) {
				Db::execute("update {$dbPrefix}api_characters set isDirector = 'F' where characterID = :char_id",
								array(":char_id" => $char_id));
		}

		if ($clearCharacter && $char_id != 0) {
				Db::execute("delete from {$dbPrefix}api_characters where user_id = :user_id and characterID = :char_id", array(":user_id" => $user_id, ":char_id" => $char_id));
		}

		if ($clearAllCharacters) {
				Db::execute("delete from {$dbPrefix}api_characters where user_id = :user_id", array(":user_id" => $user_id));
		}

		if ($clearApiEntry) {
				Db::execute("update {$dbPrefix}api set error_code = :code where user_id = :user_id", array(":user_id" => $user_id, ":code" => $code));
		}

		if ($updateCacheTime && $cacheUntil != 0 && $char_id != 0) {
				Db::execute("update {$dbPrefix}api_characters set cachedUntil = :cacheUntil where characterID = :char_id",
								array(":cacheUntil" => $cacheUntil, ":char_id" => $char_id));
				return $cacheUntil;
		}
		if ($code == 203) {
			return 1;
		}
		Log::log("Returning -1 cachedUntil for error code $code,$updateCacheTime,$cacheUntil,$char_id");
		return -1;
}

/**
 * Processes unprocessed kills
 *
 *
 * @return void
 */
function parseKills()
{
		global $dbPrefix;

		$pid = getmypid();
		if ($pid <= 3) return;
		$timer = new Timer();

		$maxTime = 60 * 1000 ;

		Db::execute("update zz_killmail set processed = 0 where processed > 3");

		while ($timer->stop() < $maxTime) {
				$yearMonths = array();
				Log::log("Fetching kills for processing...");
				$result = Db::query("select * from zz_killmail where processed = 0 order by killid desc limit 100", array(), 0);

				if (sizeof($result) == 0) {
					Log::log("No kills to process");
					return;
				}

				Log::log("Processing fetched kills...");
				foreach ($result as $row) {
						$kill = json_decode($row['kill_json']);
						if (!isset($kill->killID)) {
								Log::log("Problem with kill " . $row["killID"]);
								Db::execute("update {$dbPrefix}killmail set processed = 2 where killid = :killid", array(":killid" => $row["killID"]));
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

						try {
							Tables::ensureTableExist($year, $week);
						} catch (Exception $tableex) {
							print_r($tableex);
							continue;
						}

						// Cleanup if we're reparsing
						Db::execute("delete from {$dbPrefix}items where killID = :killID", array(":killID" => $killID));
						Db::execute("delete from {$dbPrefix}participants where killID = :killID", array(":killID" => $killID));
						Db::execute("delete from {$dbPrefix}kills where killID = :killID", array(":killID" => $killID));

						// Do some validation on the kill
						if (!validKill($kill)) {
							Db::execute("update {$dbPrefix}killmail set processed = 3 where killid = :killid", array(":killid" => $row["killID"]));
							processVictim($year, $week, $kill, $killID, $kill->victim, true);
							continue;
						}

						$totalCost = 0;
						$itemInsertOrder = 0;

						$totalCost += processItems($year, $week, $kill, $killID, $kill->items, $itemInsertOrder);
						$totalCost += processVictim($year, $week, $kill, $killID, $kill->victim);
						foreach ($kill->attackers as $attacker) processAttacker($year, $week, $kill, $killID, $attacker);
						processKill($year, $week, $kill, false, sizeof($kill->attackers), $totalCost);
						Log::log("Processed killID $killID");
						Db::execute("update {$dbPrefix}killmail set processed = 1 where killID = $killID");
						usleep(5);
				}
		}
return;
		$neededGroupIDs = Db::query("select distinct shipTypeID from zz_participants where groupID = 0 and isVictim = 'T'");
		foreach ($neededGroupIDs as $row) {
				$shipTypeID = $row["shipTypeID"];
				Log::log("Updating $shipTypeID");
				$groupID = Db::queryField("select groupID from invTypes where typeID = :id", "groupID", array(":id" => $shipTypeID));
				Db::execute("update zz_participants set groupID = :gID where shipTypeID = :id and isVictim = 'T' and groupID = 0", array(":gID" => $groupID, ":id" => $shipTypeID));
		}
		Log::log("Finished processing kills.");
		usleep(50);
}

function preFetchApis()
{
		global $dbPrefix;

		$timer = new Timer();

		$preFetched = array();

		$maxTime = 15 * 60 * 1000;

		while ($timer->stop() < $maxTime) {
				$allChars = Db::query("select user_id, characterID, isDirector from {$dbPrefix}api_characters where cachedUntil < unix_timestamp() limit 200", array(), 0);

				$count = 0;
				$total = sizeof($allChars);
				foreach ($allChars as $char) {
						if ($count > 200) continue;
						if ($timer->stop() > $maxTime) return;

						$user_id = $char['user_id'];
						$api_key = Db::queryField("select api_key from {$dbPrefix}api where user_id = :user_id", "api_key", array(":user_id" => $user_id), 300);
						$char_id = $char['characterID'];
						$isDirector = $char['isDirector'];

						if (strlen(trim($api_key)) == 0) {
								Db::execute("delete from {$dbPrefix}api_characters where user_id = :user_id", array(":user_id" => $user_id));
								continue;
						}
						$key = "$user_id $api_key $char_id $isDirector";
						if (isset($preFetched["$key"])) continue;
						$preFetched["$key"] = true;

						$count++;
						try {
								$pheal = new Pheal($user_id, $api_key, ($isDirector == "T" ? 'corp' : 'char'));
								$pheal->KillLog($isDirector == "T" ? array() : array('characterID' => $char_id));
						} catch (Exception $ex) {
								handleApiException($user_id, $char_id, $ex);
						}
				}
				sleep(1);
		}
}

function doApiSummary()
{
		global $dbPrefix;

		$charKills = Db::queryField("select contents count from {$dbPrefix}storage where locker = 'charKillsProcessed'", "count");
		$corpKills = Db::queryField("select contents count from {$dbPrefix}storage where locker = 'corpKillsProcessed'", "count");
		Db::execute("delete from {$dbPrefix}storage where locker in ('charKillsProcessed', 'corpKillsProcessed')");

		$charKills = $charKills == null ? 0 : $charKills;
		$corpKills = $corpKills == null ? 0 : $corpKills;
		$totalKills = $charKills + $corpKills;

		//if ($charKills != null && $charKills > 0) Log::irc(pluralize($charKills, "Kill") . " total pulled from Character Keys in the last 60 minutes.");
		//if ($corpKills != null && $corpKills > 0) Log::irc(pluralize($corpKills, "Kill") . " total pulled from Corporation Keys in the last 60 minutes.");
		Log::irc(pluralize($totalKills, "Kill") . " total pulled from keys since last reported.");
}

function doPopulateCharactersTable($user_id = null)
{
		global $dbPrefix;

		$specificUserID = $user_id != null;
		//if ($user_id == null) Log::irc("Repopulating character API table.");
		//else Log::irc("Populating characters for a specific user_id.");
		$apiCount = 0;
		$totalKeys = 0;
		$numErrrors = 0;
		$directorCount = 0;
		$characterCount = 0;

		$apiTableCount = Db::queryField("select count(*) count from {$dbPrefix}api", "count");
		// 15 minutes per hour, 24 hours, mean 96 checks, how many keys per 96 checks to validate all within a 24 hours period?
		$limit = (intval($apiTableCount / 96) + 1) * 3;

		if ($user_id == null) $apiKeys = Db::query("select * from {$dbPrefix}api
						where error_code != 203 and lastValidation < date_sub(now(), interval 1 day)
						order by lastValidation", array(), 0);
		else $apiKeys = Db::query("select * from {$dbPrefix}api where user_id = :user_id", array(":user_id" => $user_id));

		$validationCount = 0;
		foreach ($apiKeys as $apiKey) {
				$validationCount++;
				if ($validationCount > $limit) continue;
				$user_id = $apiKey['user_id'];
				$api_key = $apiKey['api_key'];

				Db::execute("update {$dbPrefix}api set error_code = 0, lastValidation = now() where user_id = :user_id", array(":user_id" => $user_id));

				$totalKeys++;
				$pheal = new Pheal($user_id, $api_key);
				try {
						$apiKeyInfo = $pheal->ApiKeyInfo();
						$characters = $pheal->Characters();
				} catch (Exception $ex) {
						handleApiException($user_id, null, $ex);
						$numErrrors++;
						Db::execute("update {$dbPrefix}api set error_count = error_count + 1 where user_id = :user_id", array(":user_id" => $user_id));
						continue;
				}
				Db::execute("update {$dbPrefix}api set error_count = 0 where user_id = :user_id", array(":user_id" => $user_id));
				$apiCount++;
				// Clear the error code
				$characterIDs = array();
				$pheal->scope = 'char';
				foreach ($characters->characters as $character) {
						$characterCount++;
						$characterID = $character->characterID;
						$characterIDs[] = $characterID;
						$corporationID = $character->corporationID;

						$isDirector = $apiKeyInfo->key->type == "Corporation";
						if ($isDirector) $directorCount++;
						Log::log("Populating charID $characterID, isDirector: " . ($isDirector ? "Y" : "N") . ", corporation $corporationID");

						Db::execute("insert ignore into {$dbPrefix}api_characters (user_id, characterID, corporationID, isDirector, cachedUntil)
										values (:user_id, :characterID, :corporationID, :isDirector, 0) on duplicate key update corporationID = :corporationID, isDirector = :isDirector",
										array(":user_id" => $user_id,
												":characterID" => $characterID,
												":corporationID" => $corporationID,
												":isDirector" => $isDirector ? "T" : "F",
											 ));

				}
				// Clear entries that are no longer tied to this account
				if (sizeof($characterIDs) == 0) Db::execute("delete from {$dbPrefix}api_characters where user_id = :userID", array(":userID" => $user_id));
				else Db::execute("delete from {$dbPrefix}api_characters where user_id = :userID and characterID not in (" . implode(",", $characterIDs) . ")",
								array(":userID" => $user_id));
		}

		$apiCount = number_format($apiCount, 0);
		$directorCount = number_format($directorCount, 0);
		$characterCount = number_format($characterCount, 0);
		//if (!$specificUserID) Log::irc("$apiCount keys revalidated: $directorCount Corp CEO/Directors, $characterCount Characters, and $numErrrors invalid keys.");
		//if ($specificUserID) Log::irc("Specific user_id brought in " . pluralize($directorCount, "Corp CEO/Director")
		//                              . " and " . pluralize($characterCount, "Character"));

		// Do some cleanup
		Db::execute("delete from {$dbPrefix}api where error_code = 203 or error_count > 30");
		Db::execute("update {$dbPrefix}api set error_count = 0 where error_code = 0");
}

function doPullCharKills()
{
		global $dbPrefix;
		$numKillsProcessed = 0;
		$charList = Db::query("select user_id, characterid from {$dbPrefix}api_characters where isDirector = 'F' and cachedUntil + 30 < unix_timestamp() order by cachedUntil limit 200", array(), 0);

		foreach ($charList as $char) {
				$char_id = $char['characterid'];
				$user_id = $char['user_id'];
				$api_key = Db::queryField("select api_key from {$dbPrefix}api where user_id = :user_id", "api_key", array(":user_id" => $user_id), 300);

				try {
						$pheal = new Pheal($user_id, $api_key, 'char');
						// Prefetch the killlog API to get the cachedUntil
						$killlog = $pheal->KillLog(array('characterID' => $char_id));
						$cachedUntil = $killlog->cached_until_unixtime;

						$numKillsProcessed += processApiKills($user_id, $api_key, $char_id, 'char');
				} catch (Exception $ex) {
						Log::log("Processing $user_id Char ID $char_id - " . Info::getCharName($char_id, true) . " (error)");
						print_r($ex);
						handleApiException($user_id, $char_id, $ex);
						continue;
				}
				Log::log("Processing $user_id Char ID $char_id - " . Info::getCharName($char_id, true));

				if ($cachedUntil != -1) {
						Db::execute("update {$dbPrefix}api_characters set cachedUntil = :cachedUntil, last_checked = unix_timestamp() where user_id = :user_id and characterID = :characterID",
										array(":cachedUntil" => $cachedUntil, ":user_id" => $user_id, ":characterID" => $char_id));
				}
		}

		if ($numKillsProcessed > 0) Log::log(pluralize($numKillsProcessed, "Kill") . " pulled from Character Keys.");
		if ($numKillsProcessed > 0) Db::execute("insert into {$dbPrefix}storage values ('charKillsProcessed', $numKillsProcessed) on duplicate key update contents = contents + $numKillsProcessed");
}

function doPullCorpKills()
{
		global $dbPrefix;
		$numKillsProcessed = 0;

		$corpApiCountMap = array();
		$countArray = array();
		$corpApiCountResult = Db::query("select corporationID, count(distinct characterID) count from {$dbPrefix}api_characters where isDirector = 'T' group by 1", array(), 0);
		foreach ($corpApiCountResult as $corpApi) {
				$corporationID = $corpApi['corporationID'];
				$count = max(1, min(60, $corpApi['count']));
				$corpApiCountMap["$corporationID"] = $count;
				if (!isset($countArray["$count"])) $countArray["$count"] = 0;
				$countArray["$count"]++;
		}

		$iterationCount = array();
		foreach ($countArray as $directorCount => $corpCount) {
				$corpsPerIteration = max(50, intval($corpCount / 30));
				$iterationCount["$directorCount"] = $corpsPerIteration;
		}

		$allDirectors = Db::query("select * from {$dbPrefix}api api left join {$dbPrefix}api_characters chars on api.user_id = chars.user_id 
						where isDirector = 'T' and error_code = 0", array(), 0);
		$directorsByCorp = array();
		foreach ($allDirectors as $director) {
				$corporationID = $director["corporationID"];
				if (!isset($directorsByCorp["$corporationID"])) $directorsByCorp["$corporationID"] = array();
				$directorsByCorp["$corporationID"][] = $director;
		}

		$count = 0;
		$mapSize = sizeof($corpApiCountMap);
		foreach ($corpApiCountMap as $corporationID => $directorCount) {
				$count++;
				$iterations = intval(60 / intval(60 / $directorCount));
				$intervals = intval(60 / $iterations);
				$minute = date("i");
				$limit = 0;
				while (($limit + 1) * $intervals < $minute) $limit++;

				@$corpDirectors = $directorsByCorp["$corporationID"];
				@$director = $corpDirectors["$limit"];
				if ($director == null) continue;
				if ($director['cachedUntil'] >= time()) continue;

				$iterationCountdown = isset($iterationCount["$directorCount"]) ? $iterationCount["$directorCount"] : 50;
				if ($iterationCountdown <= 0) continue;
				$iterationCount["$directorCount"] = $iterationCountdown - 1;

				$user_id = $director['user_id'];
				$api_key = $director['api_key'];
				$char_id = $director['characterID'];

				Db::execute("update {$dbPrefix}api_characters set last_checked = unix_timestamp() where user_id = :user_id and characterID = :characterID",
								array(":user_id" => $user_id, ":characterID" => $char_id));

				// Prefetch the killmail API and set the cachedUntil date
				$cachedUntil = -1;
				$pheal = new Pheal($user_id, $api_key, 'corp');
				try {
						$limit++;
						$killlog = $pheal->KillLog(/*array('characterID' => $char_id)*/);
						$cachedUntil = $killlog->cached_until_unixtime;
						$numKillsProcessed += processApiKills($user_id, $api_key, $char_id);
				} catch (Exception $ex) {
						Log::log("Processing Corp ($count/$mapSize) ID ($user_id) $corporationID $limit/$directorCount - " . Info::getCorpName($corporationID, true) . " (error)");
						$cachedUntil = handleApiException($user_id, $char_id, $ex);
						continue;
				}
				Log::log("Processing Corp ($count/$mapSize) ID ($user_id) $corporationID $limit/$directorCount - " . Info::getCorpName($corporationID, true));

				if ($cachedUntil != -1 && $cachedUntil < time()) $cachedUntil = time() + 1500;

				if ($cachedUntil != -1) {
						Db::execute("update {$dbPrefix}api_characters set cachedUntil = :cachedUntil where user_id = :user_id and characterID = :characterID",
										array(":cachedUntil" => $cachedUntil, ":user_id" => $user_id, ":characterID" => $char_id));
				} else {
						Log::log("cachedUntil is -1...");
						Db::execute("update {$dbPrefix}api_characters set cachedUntil = unix_timestamp() + 3500 where user_id = :user_id and characterID = :characterID",
										array(":user_id" => $user_id, ":characterID" => $char_id));
				}
		}

		//if ($numKillsProcessed > 10) Log::irc(pluralize($numKillsProcessed, "Kill") . " pulled from Corporate Keys.");
		if ($numKillsProcessed > 0) Db::execute("insert into {$dbPrefix}storage values ('corpKillsProcessed', $numKillsProcessed) on duplicate key update contents = contents + $numKillsProcessed");
}

function doPullKills()
{
		global $dbPrefix;

		$killsParsedAndAdded = 0;
		$apiKeys = Db::query("select * from {$dbPrefix}api where error_code = 0");
		foreach ($apiKeys as $apiKey) {
				try {
						$killsParsedAndAdded += processKey($apiKey['user_id'], $apiKey['api_key'], 'char');
				} catch (Exception $ex) {
						echo $ex->getMessage() . "\n";
				}
		}
		return $killsParsedAndAdded;
}

function processKey($userID, $apiKey, $scope)
{
		// Get the characters on the key
		$pheal = new Pheal($userID, $apiKey);
		$characters = $pheal->Characters();
		$killsParsedAndAdded = 0;

		foreach ($characters->characters as $character) {
				$charID = $character['characterID'];
				try {
						$killsParsedAndAdded += processApiKills($userID, $apiKey, $charID, $scope);
				} catch (Exception $ex) {
						//echo "$userID, $apiKey, $charID, $scope Error: ",$ex->getCode(),$ex->getMessage(),"\n";
				}
		}
		return $killsParsedAndAdded;
}

/*
   Process Killmails pulled from the full API
   Ignores kills where NPC are the only attackers
 */
function processApiKills($userID, $userKey, $charID, $scope = "corp")
{
		global $dbPrefix;
		$killsParsedAndAdded = 0;

		$pheal = new Pheal($userID, $userKey, $scope);
		$array = array();
		if ($scope != "corp") $array['characterID'] = $charID;
		$killlog = $pheal->KillLog($array);
		if (sizeof($killlog->kills) == 0) return 0;

		$allKillIds = array();
		foreach ($killlog->kills as $kill) {
				$allKillIds[] = "(" . $kill->killID . ")";
		}

		$ids = implode(",", $allKillIds);

		$maxKillId = Db::queryField("select max_kill_id from {$dbPrefix}api_characters where user_id = :user_id and characterID = :char_id", "max_kill_id",
						array(":user_id" => $userID, ":char_id" => $charID));

		$maxKillIdProcessed = 0;
		foreach ($killlog->kills as $kill) {
				$killID = $kill->killID;
				$maxKillIdProcessed = max($maxKillIdProcessed, max(intval($maxKillId), intval($killID)));

				if (intval($killID) <= intval($maxKillId)) continue;

				$json = json_encode($kill->toArray());
				$added = Db::execute("insert ignore into {$dbPrefix}killmail (killID, kill_json) values (:killID, :json)",
								array(":killID" => $killID, ":json" => $json));
				if ($added > 0) {
						$killsParsedAndAdded += $added;
						Log::log("Added for parse killID $killID");
				}
		}

		if ($maxKillIdProcessed != $maxKillId) {
				Db::execute("update {$dbPrefix}api_characters set max_kill_id = :maxKillId where user_id = :user_id and characterID = :char_id",
								array(":user_id" => $userID, ":char_id" => $charID, ":maxKillId" => $maxKillIdProcessed));
		}

		return $killsParsedAndAdded;
}

function processRawApi($killlog) {
		global $dbPrefix;

		foreach ($killlog->kills as $kill) {
				$killID = $kill->killID;

				$count = Db::queryField("select count(1) count from {$dbPrefix}killmail where killID = :killID limit 1", "count", array(":killID" => $killID));
				if ($count > 0) return;

				$json = json_encode($kill->toArray());
				$added = Db::execute("insert ignore into {$dbPrefix}killmail (killID, kill_json) values (:killID, :json)",
								array(":killID" => $killID, ":json" => $json));
				if ($added === FALSE) Log::log("Error inserting $killID");
				else if ($added == 1) Log::log("Added kill $killID");
		}
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
				// Don't process the kill if it's NPC only
				$npcOnly &= $attacker->characterID == 0 && $victimCorp < 1999999;
				// Check for blue on blue
				if ($attacker->characterID != 0) $blueOnBlue &= $victimCorp == $attacker->corporationID && $victimAlli == $attacker->allianceID;
		}
		if ($npcOnly || $blueOnBlue) return false;

		return true;
}

function processKill(&$year, &$week, &$kill, $npcOnly, $number_involved, $totalCost)
{
		global $dbPrefix;

		$date = $kill->killTime;

		$date = strtotime($date);
		$month = date("m", $date);
		$day = date("d", $date);
		$unix = date("U", $date);

		$month = strlen("$month") < 2 ? "0$month" : $month;

		Db::execute("
						insert into {$dbPrefix}kills
						(killID, solarSystemID, killTime, moonID, year, month, week, day,
						 unix_timestamp, npcOnly, number_involved, total_price, processed_timestamp)
						values
						(:killID, :solarSystemID, :killTime, :moonID, :year, :month, :week, :day,
						 :unix_timestamp, :npcOnly, :number_involved, :total_price, :unix_timestamp)",
						(array(
							   ":killID" => $kill->killID,
							   ":solarSystemID" => $kill->solarSystemID,
							   ":killTime" => $kill->killTime,
							   ":moonID" => $kill->moonID,
							   ":year" => $year,
							   ":month" => $month,
							   ":week" => $week,
							   ":day" => $day,
							   ":unix_timestamp" => $unix,
							   ":npcOnly" => $npcOnly,
							   ":number_involved" => $number_involved,
							   ":total_price" => $totalCost,
							  )));
		Memcached::set("LAST_KILLMAIL_PROCESSED", $unix);
}

function processVictim(&$year, &$week, &$kill, $killID, &$victim, $isNpcVictim = false)
{
		global $dbPrefix;

		$shipPrice = Price::getItemPrice($victim->shipTypeID);

		if (!$isNpcVictim) Db::execute("
						insert into {$dbPrefix}participants
						(killID, isVictim, shipTypeID, shipPrice, damage, factionID, allianceID,
						 corporationID, characterID, year, week)
						values
						(:killID, 'T', :shipTypeID, :shipPrice, :damageTaken, :factionID, :allianceID,
						 :corporationID, :characterID, :year, :week)",
						(array(
							   ":killID" => $killID,
							   ":shipTypeID" => $victim->shipTypeID,
							   ":shipPrice" => $shipPrice,
							   ":damageTaken" => $victim->damageTaken,
							   ":factionID" => $victim->factionID,
							   ":allianceID" => $victim->allianceID,
							   ":corporationID" => $victim->corporationID,
							   ":characterID" => $victim->characterID,
							   ":year" => $year,
							   ":week" => $week,
							  )));

		if ($victim->characterID != 0)
				Db::execute("insert ignore into {$dbPrefix}characters (character_id, name) values (:id, :name)", 
								array(":id" => $victim->characterID, ":name" => $victim->characterName));
		if ($victim->corporationID != 0) 
				Db::execute("insert ignore into {$dbPrefix}corporations (corporation_id, name) values (:id, :name)",
								array(":id" => $victim->corporationID, ":name" => $victim->corporationName));
		if ($victim->allianceID != 0) 
				Db::execute("insert ignore into {$dbPrefix}alliances (alliance_id, name) values (:id, :name)",
								array(":id" => $victim->allianceID, ":name" => $victim->allianceName));
		if ($victim->factionID != 0) 
				Db::execute("insert ignore into {$dbPrefix}factions (faction_id, name) values (:id, :name)",
								array(":id" => $victim->factionID, ":name" => $victim->factionName));

		return $shipPrice;
}

function processAttacker(&$year, &$week, &$kill, &$killID, &$attacker)
{
		global $dbPrefix;

		Db::execute("
						insert into {$dbPrefix}participants
						(killID, isVictim, characterID, corporationID, allianceID, 
						 factionID, securityStatus, damage, finalBlow, weaponTypeID, shipTypeID, year, week)
						values
						(:killID, 'F', :characterID, :corporationID, :allianceID,
						 :factionID, :securityStatus, :damageDone, :finalBlow, :weaponTypeID, :shipTypeID, :year, :week)",
						(array(
							   ":killID" => $killID,
							   ":characterID" => $attacker->characterID,
							   ":corporationID" => $attacker->corporationID,
							   ":allianceID" => $attacker->allianceID,
							   ":factionID" => $attacker->factionID,
							   ":securityStatus" => $attacker->securityStatus,
							   ":damageDone" => $attacker->damageDone,
							   ":finalBlow" => $attacker->finalBlow,
							   ":weaponTypeID" => $attacker->weaponTypeID,
							   ":shipTypeID" => $attacker->shipTypeID,
							   ":year" => $year,
							   ":week" => $week
							  )));


		if ($attacker->characterID != 0)
				Db::execute("insert ignore into {$dbPrefix}characters (character_id, name) values (:id, :name)", 
								array(":id" => $attacker->characterID, ":name" => $attacker->characterName));
		if ($attacker->corporationID != 0) 
				Db::execute("insert ignore into {$dbPrefix}corporations (corporation_id, name) values (:id, :name)",
								array(":id" => $attacker->corporationID, ":name" => $attacker->corporationName));
		if ($attacker->allianceID != 0) 
				Db::execute("insert ignore into {$dbPrefix}alliances (alliance_id, name) values (:id, :name)",
								array(":id" => $attacker->allianceID, ":name" => $attacker->allianceName));
		if ($attacker->factionID != 0) 
				Db::execute("insert ignore into {$dbPrefix}factions (faction_id, name) values (:id, :name)",
								array(":id" => $attacker->factionID, ":name" => $attacker->factionName));

}

function processItems(&$year, &$week, &$kill, &$killID, &$items, &$itemInsertOrder, $isCargo = false) {
		$totalCost = 0;
		foreach ($items as $item) {
				$totalCost += processItem($year, $week, $kill, $killID, $item, $itemInsertOrder++, $isCargo);
				if (@is_array($item->items)) {
						$totalCost += processItems($year, $week, $kill, $killID, $item->items, $itemInsertOrder, true);
				}
		}
		return $totalCost;
}

function processItem(&$year, &$week, &$kill, &$killID, &$item, $itemInsertOrder, $isCargo = false)
{
		global $dbPrefix;

		$price = Price::getItemPrice($item->typeID);
		$itemName = Info::getItemName($item->typeID);
		if ($isCargo && strpos($itemName, "Blueprint") !== false) $item->singleton = 2;
		if ($item->singleton == 2) { 
				$price = $price / 100;
		}

		Db::execute("
						insert into {$dbPrefix}items
						(killID, typeID, flag, qtyDropped, qtyDestroyed, insertOrder, price, singleton, year, week)
						values
						(:killID, :typeID, :flag, :qtyDropped, :qtyDestroyed, :insertOrder, :price, :singleton, :year, :week)
						on duplicate key update price = :price",
						(array(
							   ":killID" => $killID,
							   ":typeID" => $item->typeID,
							   ":flag" => ($isCargo ? 5 : $item->flag),
							   ":qtyDropped" => $item->qtyDropped,
							   ":qtyDestroyed" => $item->qtyDestroyed,
							   ":insertOrder" => $itemInsertOrder,
							   ":price" => $price,
							   ":singleton" => $item->singleton,
							   ":year" => $year,
							   ":week" => $week,
							  )));

		return ($price * ($item->qtyDropped + $item->qtyDestroyed));
}
