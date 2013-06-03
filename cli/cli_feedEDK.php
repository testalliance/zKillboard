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

class cli_feedEDK implements cliCommand
{
	public function getDescription()
	{
		return "Manages the external EDK feeds. You can add, remove, list and fetch. |w|Beware, this is a persistent method. It's run and forget!.|n| |g|Usage: feed <method>";
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
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|help <command>|n| To see a list of commands, use: |g|list", true);
		$command = $parameters[0];

		switch($command)
		{
			case "add":
				$url = $parameters[1];
				if(filter_var($url, FILTER_VALIDATE_URL))
				{
					Db::execute("INSERT INTO zz_feeds (url, edkStyle) VALUES (:url, 1)", array(":url" => $url));
					CLI::out("Now inserting |g|$url|n| to the database.", true);
				}
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
					$url = Db::queryField("SELECT url FROM zz_feeds WHERE id = :id", "url", array(":id" => $id));
					if(is_null($url))
						CLI::out("|r|Feed is already removed.", true);
					CLI::out("Removing feed: |g|$url");
					Db::execute("DELETE FROM zz_feeds WHERE id = :id", array(":id" => $id));
				}
			break;

			case "list":
				$list = Db::query("SELECT * FROM zz_feeds");
				foreach($list as $url)
					CLI::out($url["id"]."::|g|".$url["url"]);
			break;

			case "fetch":
				CLI::out("|g|Initiating feed fetching|n|");
				$doSleep = false;
				$size = 1;
				$count = 1;
				$feeds = Db::query("SELECT id, url, lastFetchTime FROM zz_feeds WHERE edkStyle IS true", array(), 0);
				if(sizeof($feeds) > 1)
				{
					$doSleep = true;
					$size = sizeof($feeds);
				}

				foreach($feeds as $feed)
				{
					$url = $feed["url"];
					$source = "EDK:".$feed["id"];
					$lastFetchTime = strtotime($feed["lastFetchTime"])+600;
					$currentTime = time();
					$insertCount = 0;
					if($lastFetchTime <= $currentTime)
					{
						CLI::out("Fetching from |g|$url|n|");
						try
						{
							$data = self::fetchUrl($url);
							$xml = new SimpleXMLElement($data);
							$result = new PhealResult($xml);
							$insertCount = self::processAPI($result);
							//Db::execute("UPDATE zz_feeds SET lastFetchTime = :time WHERE url = :url", array(":time" => date("Y-m-d H:i:s"), ":url" => $url));
							if($insertCount > 0)
							{
								CLI::out("Inserted |g|$insertCount|n| new kills from |g|$url|n|");
								Log::log("Inserted $insertCount new kills from $url");
							}
							else
								CLI::out("No new kills from |r|$url|n|");
						}
						catch (Exception $ex)
						{
							CLI::out("|r|Error with $url: ". $ex->getMessage());
						}

						if($doSleep && $size != $count)
						{
							CLI::out("|g|Sleeping for 10 seconds|n| before fetching another url.. (Otherwise we're hammering..)");
							sleep(10); // yes yes, 10 seconds of sleeping, what?! this is only here to stop hammering. Feel free to hammer tho by commenting this, but you'll just get banned..
						}
						$count++;
					}
				}
			break;
		}
	}

	private static function fetchUrl($url)
	{
		global $baseAddr;
		$userAgent = "EDK Style Feed Fetcher for $baseAddr";

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

	private static function processAPI($data)
	{
		$count = 0;

		foreach ($data->kills as $kill) {
			unset($kill->killInternalID);
			unset($kill->hash);
			unset($kill->trust);

			var_dump($kill); die();
			if($kill->killInternalID)
			{
				die($kill->killInternalID);
				$killID = $kill->killID * -1;
			}
			else
				$killID = $kill->killID;

			if($killID == 0)
				continue;

			$json = json_encode($kill->toArray());
			$hash = Util::getKillHash(null, $kill);
			$mKillID = Db::queryField("select killID from zz_killmails where killID < 0 and processed = 1 and hash = :hash", "killID", array(":hash" => $hash), 0);
			if ($mKillID) Kills::cleanDupe($mKillID, $killID);

			$added = Db::execute("insert ignore into zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
					array(":killID" => $killID, ":hash" => $hash, ":source" => $keyID, ":json" => $json));
			$count += $added;
		}

		return $count;
	}
}