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

class cli_apiFetchKillLog implements cliCommand
{
	public function getDescription()
	{
		return "Fetches the kill logs from the CCP API. |g|Usage: apiFetchKillLog";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters)
	{
		@$apiRowID = $parameters[0];

		$apiRow = Db::queryRow("select * from zz_api_characters where apiRowID = :id", array(":id" => $apiRowID), 0);

		if (!$apiRow) CLI::out("|r|No such apiRowID: $apiRowID", true);

		$keyID = $apiRow["keyID"];
		$vCode = Db::queryField("select vCode from zz_api where keyID = :keyID", "vCode", array(":keyID" => $keyID));
		$isDirector = $apiRow["isDirector"];
		$charID = $apiRow["characterID"];

		if ($keyID == "" || $vCode == "") die("no keyID or vCode");

		try {
			$pheal = Util::getPheal($keyID, $vCode);
			$charCorp = ($isDirector == "T" ? 'corp' : 'char');
			$pheal->scope = $charCorp;
			$result = null;

			// Update last checked
			Db::execute("update zz_api_characters set errorCode = 0, lastChecked = now() where apiRowID = :id", array(":id" => $apiRowID));

			if ($isDirector == "T") $result = $pheal->KillLog();
			else $result = $pheal->KillLog(array('characterID' => $charID));

			$cachedUntil = $result->cached_until;
			if ($cachedUntil == "") $cachedUntil = 0;
			Db::execute("update zz_api_characters set cachedUntil = if(:cachedUntil = 0, date_add(now(), interval 1 hour), :cachedUntil), errorCode = '0' where apiRowID = :id", array(":id" => $apiRowID, ":cachedUntil" => $cachedUntil));

			$file = "/var/killboard/zkb_killlogs/{$keyID}_{$charID}_0.xml";
			@unlink($file);
			error_log($pheal->xml . "\n", 3, $file);

			$aff = Api::processRawApi($keyID, $charID, $result);
			if ($aff > 0) {
				$keyID = "$keyID";
				while (strlen($keyID) < 8) $keyID = " " . $keyID;
				Log::log("KeyID: $keyID ($charCorp) added $aff kill" . ($aff == 1 ? "" : "s"));
			}
		} catch (Exception $ex) {
			$errorCode = $ex->getCode();
			Db::execute("update zz_api_characters set cachedUntil = date_add(now(), interval 1 hour), errorCode = :code where apiRowID = :id", array(":id" => $apiRowID, ":code" => $errorCode));
			switch($errorCode) {
				case 119:
				case 120:
					// Don't log it
				break;	
				case 222: // API has expired
				case 221: // Invalid access, delete the toon from the char list until later re-verification
				case 220: // Invalid Corporation Key. Key owner does not fullfill role requirements anymore.
					Db::execute("delete from zz_api_characters where apiRowID = :id", array(":id" => $apiRowID));
				break;
				default:
					Log::log($keyID . " " . $ex->getCode() . " " . $ex->getMessage());
			}
			return;
		}
	}
}
