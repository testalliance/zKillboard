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

require_once( dirname(__FILE__) . "/../init.php" );
require_once( dirname(__FILE__) . "/cron.php" );

$keyID = (int) $argv[1];
$vCode = Db::queryField("select vCode from zz_api where keyID = :keyID", "vCode", array(":keyID" => $keyID), 0);

if ($keyID == 0 && strlen($vCode) == 0) return;

$pheal = Util::getPheal($keyID, $vCode);
try {
	$apiKeyInfo = $pheal->ApiKeyInfo();
} catch (Exception $ex) {
	//Log::log("Error with $keyID: " . $ex->getCode() . " " . $ex->getMessage());
	handleApiException($keyID, null, $ex);
	return;
}

Db::execute("update zz_api set lastValidation = now() where keyID = :keyID", array(":keyID" => $keyID));

// Clear the error code
$characterIDs = array();            
$pheal->scope = 'char';
foreach ($apiKeyInfo->key->characters as $character) {
	$characterID = $character->characterID;
	$characterIDs[] = $characterID;
	$corporationID = $character->corporationID;

	$isDirector = $apiKeyInfo->key->type == "Corporation";
	if ($isDirector) $directorCount++;
	$m = Db::execute("insert ignore into zz_api_characters (keyID, characterID, corporationID, isDirector, cachedUntil)
			values (:keyID, :characterID, :corporationID, :isDirector, 0) on duplicate key update corporationID = :corporationID, isDirector = :isDirector",
			array(":keyID" => $keyID,
				":characterID" => $characterID,
				":corporationID" => $corporationID,
				":isDirector" => $isDirector ? "T" : "F",
				));

	if ($m > 0) {
		while (strlen($keyID) < 8) $keyID = " " . $keyID;
		$charCorp =  ($isDirector ? "corp" : "char");
		$charName = Info::getCharName($characterID, true);
		$corpName = Info::getCorpName($corporationID, true);
		Log::log("KeyID: $keyID ($charCorp) Populating: $charName / $corpName");
	}
}
// Clear entries that are no longer tied to this account
if (sizeof($characterIDs) == 0) Db::execute("delete from zz_api_characters where keyID = :keyID", array(":keyID" => $keyID));
else Db::execute("delete from zz_api_characters where keyID = :keyID and characterID not in (" . implode(",", $characterIDs) . ")",
		array(":keyID" => $keyID));
