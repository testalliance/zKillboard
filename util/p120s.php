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

$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/cron.php";


$api120 = Db::query("select * from zz_api_characters where errorCode in (120)", array(), 0);

foreach($api120 as $api) {
	$keyID = $api["keyID"];
	$vCode = Db::queryField("select vCode from zz_api where keyID = $keyID", "vCode", array(), 300);
	$isDirector = $api["isDirector"];
	$charID = $api["characterID"];

	try {
    	$pheal = Util::getPheal($keyID, $vCode);
    	$pheal->scope = ($isDirector == "T" ? 'corp' : 'char');

		if ($isDirector == "T") $pheal->KillLog();
		else $pheal->KillLog(array('characterID' => $charID));
		Db::execute("update zz_api_characters set errorCode = 0, lastChecked = 0, cachedUntil = 0 where keyID = $keyID and characterID = $charID");
	} catch (Exception $ex) {
		if ($ex->getCode() == 120) {
			$msg = $ex->getMessage();
			$msg = str_replace("Expected beforeKillID [", "", $msg);
			$pos = strpos($msg, "]");
			if ($pos !== false) {
				$beforeKillID = substr($msg, 0, $pos);
				//echo "$beforeKillID\n";
				try {
					$result = $pheal->KillLog(array('characterID' => $charID, "beforeKillID" => $beforeKillID));
				} catch (Exception $ex) { continue; }
				$cachedUntil = $result->cached_until_unixtime;
				$new = processRawApi($keyID, $charID, $result);
				if ($new) Log::log("(120) $keyID - $new kills");

				$file = "/var/killboard/zkb_killlogs/{$keyID}_{$charID}_0.xml";
				@unlink($file);
				error_log($result->xml . "\n", 3, $file);

				if ($cachedUntil < time()) $cachedUntil = time() + 3600;
				Db::execute("update zz_api_characters set errorCode = 0, cachedUntil = :cachedUntil, lastChecked = unix_timestamp() where keyID = :keyID and characterID = :characterID",
						array(":cachedUntil" => $cachedUntil, ":keyID" => $keyID, ":characterID" => $charID));

			}
		} else handleApiException($keyID, $charID, $ex);
	}
	sleep(1);
}
