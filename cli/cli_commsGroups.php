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

class cli_commsGroups implements cliCommand
{
	public function getDescription()
	{
		return "Finds which groups characters belong to, and put them into said group";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters)
	{
		$groups = "/var/killboard/jabbergroups.txt";
		unlink($groups);
		$jabberServer = "eve-kill.net";
		$groupsArray = array();

		$chars = Db::query("SELECT id, value FROM zz_users_config WHERE `key` = :key", array(":key" => "defaultOogCommsCharacter"));
		$userList = array();

		foreach($chars as $id)
		{
			$name = str_replace('"', '', $id["value"]);
			$userList[$name] = $id["id"];
		}

		foreach($userList as $characterID => $userID)
		{
			$api = Api::getCharacterKeys($userID);
			foreach($api as $entity)
			{
				if($entity["characterID"] == $characterID)
				{
					$username = Db::queryField("SELECT username FROM zz_users WHERE id = :id", "username", array(":id" => $userID));
					$characterName = Info::getCharName($characterID);
					$corporationName = Info::getCorpName($entity["corporationID"]);

					$groupsArray[$corporationName][] = "$username@$jabberServer=$characterName";
					$allianceID = Db::queryField("SELECT allianceID FROM zz_corporations WHERE corporationID = :corpID", "allianceID", array(":corpID" => $entity["corporationID"]));
					$allianceName = NULL;
					if(!empty($allianceID))
					{
						$allianceName = Info::getAlliName($allianceID);
						$groupsArray[$allianceName][] = "$username@$jabberServer=$characterName";
					}
					$groupsArray["Public"][] = "$username@$jabberServer=$characterName";
				}
			}
		}

		$text = NULL;
		foreach($groupsArray as $entity => $user)
		{
			$text .= "\n[$entity]\n";
			foreach($user as $user)
				$text .= "$user\n";
		}

		file_put_contents($groups, $text);
		exec("prosodycmd 'module:reload(\"groups\")'");
		exec("prosodycmd 'module:reload(\"roster\")'");
		exec("prosodycmd 'module:reload(\"presence\")'");
		exec("prosodycmd 'config:reload()'");
	}
}
