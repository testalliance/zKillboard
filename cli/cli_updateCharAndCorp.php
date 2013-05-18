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

class cli_updateCharAndCorp implements cliCommand
{
	public function getDescription()
	{
		return "Updates the corporation / character Name and IDs. |g|Usage: updateCharAndCorp <type>";
	}

	public function getAvailMethods()
	{
		return "all characters corporations"; // Space seperated list
	}

	public function execute($parameters)
	{
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("", true);
		$command = $parameters[0];

		switch($command)
		{
			case "all":
				self::updateCharacters();
				self::updateCorporations();
			break;

			case "characters":
				self::updateCharacters();
			break;

			case "corporations":
				self::updateCorporations();
			break;
		}
	}

	private static function updateCharacters()
	{
		CLI::out("|g|Updating characters");
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
			Db::execute("update zz_characters set lastUpdated = now() where characterID = :id", array(":id" => $id));

			$pheal = Util::getPheal();
			$pheal->scope = "eve";
			try
			{
				$charInfo = $pheal->CharacterInfo(array("characterid" => $id));
				$name = $charInfo->characterName;
				$corpID = $charInfo->corporationID;
				$alliID = $charInfo->allianceID;
				CLI::out("|g|$name|n| $id $corpID $alliID");
				Db::execute("update zz_characters set name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" => $name, ":corpID" => $corpID, ":alliID" => $alliID));
			}
			catch (Exception $ex)
			{
				// Is this name even a participant?
				$count = Db::queryField("select count(*) count from zz_participants where characterID = :id", "count", array(":id" => $id));
				if ($count == 0)
					Db::execute("delete from zz_characters where characterID = :id", array(":id" => $id));
				elseif ($ex->getCode() != 503)
					Log::log("ERROR Validating Character $id" . $ex->getMessage());
			}
			//$json = file_get_contents("http://evewho.com/ek_pilot_id.php?id=$id");
			//$info = json_decode($json, true);
			//Db::execute("update zz_characters set lastUpdated = now(), name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" => $info["name"], ":corpID" => $info["corporation_id"], ":alliID" => $info["alliance_id"]));
			//usleep(100000);
		}
	}

	private static function updateCorporations()
	{
		CLI::out("|g|Updating corporations");
		Db::execute("delete from zz_corporations where corporationID = 0");
		Db::execute("insert ignore into zz_corporations (corporationID) select executorCorpID from zz_alliances where executorCorpID > 0");
		$result = Db::query("select corporationID, name, memberCount, ticker from zz_corporations where (memberCount is null or memberCount > 0 or lastUpdated = 0)  and corporationID >= 1000001 order by lastUpdated limit 100", array(), 0);
		foreach($result as $row) {
			$id = $row["corporationID"];
			$oMemberCount = $row["memberCount"];
			$oName = $row["name"];
			$oTicker = $row["ticker"];

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
				CLI::out("|g|$id|n| $Name");
				Db::execute("update zz_corporations set name = :name, ticker = :ticker, memberCount = :memberCount, ceoID = :ceoID, description = :dscr, lastUpdated = now() where corporationID = :id",
						array(":id" => $id, ":name" => $name, ":ticker" => $ticker, ":memberCount" => $memberCount, ":ceoID" => $ceoID, ":dscr" => $dscr));

			} catch (Exception $ex) {
				Db::execute("update zz_corporations set lastUpdated = now() where corporationID = :id", array(":id" => $id));
				if ($ex->getCode() != 503) Log::log("ERROR Validating Corp $id: " . $ex->getMessage());
			}
			usleep(100000);
		}
	}
}
