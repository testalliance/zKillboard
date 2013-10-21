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

class cli_feedzKB implements cliCommand
{
	public function getDescription()
	{
		return "Manages the external feeds. You can add, remove, list and fetch. |w|Beware, this is a persistent method. It's run and forget!.|n| |g|Usage: feed <method>\n
		|n|You can do a fetch all by issuing |g|feed fetch all|n| Be patient, this could take awhile!";
	}

	public function getAvailMethods()
	{
		return "add remove list fetch"; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(
			3600 => "fetch"
		);
	}

	public function execute($parameters)
	{
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|feedzKB <command>|n| To see a list of commands, use: |g|list", true);
		$command = $parameters[0];

		switch($command)
		{
			case "add":
				if(isset($parameters[1])) // Skip the creation tutorial
				{
					$url = $parameters[1];
				}
				else
				{
					CLI::out("|g|You are now adding a feed with the quick feed creator(TM), if you already have a feed you want to add, use add <feed> instead.|n|");
					$addr = CLI::prompt("Start of address. |g|Ex: zkillboard.com|n|", "zkillboard.com");

					retryCall:
					$validTypes = array("characterID", "corporationID", "allianceID", "factionID", "shipTypeID", "groupID", "solarSystemID", "regionID");
					CLI::out("|g|Valid Calls:|n| ". implode(", ", $validTypes));
					$type = CLI::prompt("Type of call. |g|(Refer to the above list)|n|");
					if(!in_array($type, $validTypes))
					{
						CLI::out("|r|Error with the type of call, please retry|n|");
						goto retryCall;
					}

					$id = (int) CLI::prompt("|g|ID|n| of entity you wish to fetch for.");

					retryApiOnly:
					CLI::out("|g|Valid Calls:|n| yes, no");
					$apiOnly = CLI::prompt("API Only?", "yes");
					if($apiOnly != "yes" && $apiOnly != "no")
					{
						CLI::out("|r|Error, call type is not supported. Please retry|n|");
						goto retryApiOnly;
					}

					retryCombined:
					CLI::out("|g|Valid Calls:|n| yes, no");
					$combined = CLI::prompt("Combined kills and losses?", "yes");
					if($combined != "yes" && $combined != "no")
					{
						CLI::out("|r|Error, call type is not supported. Please retry|n|");
						goto retryCombined;
					}				
					if($combined == "no")
					{
						retryKills:
						CLI::out("|g|Valid Calls:|n| kills, losses");
						$kills = CLI::prompt("Kills or Losses only?", "kills");
						if($kills != "kills" && $kills != "losses")
						{
							CLI::out("|r|Error, call type is not supported. Please retry|n|");
							goto retryKills;
						}
					}

					$url = "http://".$addr."/api/";
					if($combined == "no")
						$url .= ($kills ? "kills" : "losses")."/";
					if($apiOnly == "yes")
						$url .= "api-only/";
					$url .= $type."/";
					$url .= $id."/";
				}

				if(filter_var($url, FILTER_VALIDATE_URL))
				{
					CLI::out("Now inserting |g|$url|n| to the database.");
					Db::execute("INSERT INTO zz_feeds (url) VALUES (:url)", array(":url" => $url));
				}
				else
					CLI::out("|r|Invalid URL, please try again|n| - $url", true);
			break;

			case "remove":
				$id = NULL;
				if(isset($parameters[1]))
					$id = $parameters[1];

				if(is_null($id))
					CLI::out("Please refer to feed list to show all the feeds you have added to your board. To remove one, use: |g|feed remove <id>|n|");
				elseif(!is_numeric($id))
					CLI::out("|r|ID needs to be an int..|n|");
				else
				{
					$url = Db::queryField("SELECT url FROM zz_feeds WHERE id = :id AND edkStyle = 0", "url", array(":id" => $id));
					if(is_null($url))
						CLI::out("|r|Feed is already removed.", true);
					CLI::out("Removing feed: |g|$url");
					Db::execute("DELETE FROM zz_feeds WHERE id = :id", array(":id" => $id));
				}
			break;

			case "list":
				$list = Db::query("SELECT * FROM zz_feeds WHERE edkStyle = 0");
				foreach($list as $url)
					CLI::out($url["id"]."::|g|".$url["url"]."|n|");
			break;

			case "fetch":
				$fetchAll = isset($parameters[1]) && $parameters[1] == "all";

				if ($fetchAll)
					$feeds = Db::query("SELECT id, url, lastFetchTime FROM zz_feeds WHERE edkStyle = '0' order by id");
				else
					$feeds = Db::query("SELECT id, url, lastFetchTime FROM zz_feeds where edkStyle = '0' AND lastFetchTime < date_sub(now(), interval 1 hour) order by id");

				$totalCount = 0;

				foreach($feeds as $feed)
				{
					$url = $feed["url"];
					$insertCount = 0;
					CLI::out("Fetching for |g|$url|n|");
					$page = 1;
					$retryCount = 0;

					do
					{
						retry:
						$fetchedData = self::fetchUrl($url . ($fetchAll ? "page/$page/" : ""));
						if ($fetchedData == "")
						{
							CLI::out("|r|Remote server returned an invalid response, moving along after 15 seconds...|n|");
							sleep(15);
							continue;
						}

						$data = json_decode($fetchedData);
						$insertCount = 0;
						if (sizeof($data) == 0)
						{
							CLI::out("|r|Remote server returned no data, lets wait 15 seconds...|n|");
							sleep(15);
							if($retryCount > 0)
							{
								$retryCount = 0;
								CLI::out("Moving along...");
								continue;
							}
							else
							{
								$retryCount = 1;
								CLI::out("Retrying...");
								goto retry;
							}
						}

						foreach($data as $kill)
						{
							if(isset($kill->_stringValue))
								unset($kill->_stringValue);

							$hash = Util::getKillHash(null, $kill);
							$json = json_encode($kill);
							$killID = $kill->killID;
							$source = "zKB Feed Fetch";

							$insertCount += Db::execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) VALUES (:killID, :hash, :source, :kill_json)",
								array(":killID" => $killID, ":hash" => $hash, ":source" => $source, ":kill_json" => $json));
						}

						Db::execute("UPDATE zz_feeds SET lastFetchTime = now() WHERE url = :url", array(":url" => $url));

						$totalCount += $insertCount;
						CLI::out("Inserted |g|$insertCount|n|/|g|" . sizeof($data) . "|n| kills...");
						Log::log("Inserted $insertCount new kills from $url");

						if(sizeof($feeds) > 1 || $page >= 1)
						{
							CLI::out("|g|Pausing|n| for 15 seconds...");
							sleep(15);
						}
						$page++;
					} while (($fetchAll == true && sizeof($data) > 0));
				}

				if ($totalCount > 0)
					CLI::out("Inserted a total of |g|" . number_format($totalCount, 0) . "|n| kills.");
			break;
		}
	}

	private static function fetchUrl($url)
	{
		global $baseAddr;
		$userAgent = "Feed Fetcher for $baseAddr";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl, CURLOPT_ENCODING, "");
		$headers = array();
		$headers[] = "Connection: keep-alive";
		$headers[] = "Keep-Alive: timeout=10, max=1000";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);

		return $result;
	}
}
