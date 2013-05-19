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

class cli_populate implements cliCommand
{
	public function getDescription()
	{
		return "Populates the Characters and Alliance tables. |w|Beware, this is a semi-persistent script.|n| |g|Usage: populate <type>";
	}

	public function getAvailMethods()
	{
		return "characters alliances all"; // Space seperated list
	}

	public function execute($parameters)
	{
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("", true);
		$command = $parameters[0];

		switch($command)
		{
			case "all":
				self::populateAlliances();
				self::PopulateCharaters();
			break;

			case "characters":
				self::PopulateCharaters();
			break;

			case "alliances":
				self::populateAlliances();
			break;
		}
	}

	private static function PopulateCharaters()
	{
		CLI::out("This is a semi-persistent script.");
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
				$command = "flock -w 60 /tmp/locks/preFetchChars.$m zkillboard apiFetchCharacters $keyID"; // REMEMBER TO CHECK IF THIS SHIT WORKS......
				$command = escapeshellcmd($command);
				//Log::log($command);
				exec("$command >/dev/null 2>/dev/null &");
				$iterationCount++;
				if ($iterationCount % $fetchesPerSecond == 0) sleep(1);
			}
		}
	}

	private static function populateAlliances()
	{
		CLI::out("Repopulating the alliance table");
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
			CLI::out("Alliance tables repopulated - $allianceCount active Alliances with a total of $corporationCount Corporations");
			Log::log("Alliance tables repopulated - $allianceCount active Alliances with a total of $corporationCount Corporations");
		} else {
			CLI::out("Unable to pull Alliance XML from API.  Will try again later.");
			Log::log("Unable to pull Alliance XML from API.  Will try again later.");
			if ($exception != null) throw $exception;
			throw new Exception("Unable to pull Alliance XML from API.  Will try again later");
		}	
	}
}
