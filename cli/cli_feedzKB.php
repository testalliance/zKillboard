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
		return "Fetches feeds from other zKillboards";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(
				30 => "fetch"
				);
	}

	public function execute($parameters)
	{
		if (Util::isMaintenanceMode()) return;

		$feeds = Db::query("SELECT * FROM zz_feeds where edkStyle = '0' order by lastFetchTime");

		$totalCount = 0;

		foreach($feeds as $feed)
		{
			$id = $feed["id"];
			$baseurl = $feed["url"];
			CLI::out("Fetching for |g|$baseurl|n|");
			$lastKillTime = $feed["lastKillTime"];

			do
			{
				$insertCount = 0;
				$url = "$baseurl/orderDirection/asc/";
				if ($lastKillTime != null) $url .= "startTime/" . preg_replace( '/[^0-9]/', '', $lastKillTime ) . "/";
				CLI::out($url);
				$fetchedData = self::fetchUrl($url);
				if ($fetchedData == "")
				{
					CLI::out("|r|Remote server returned an invalid response, moving along after 15 seconds...|n|");
					sleep(15);
					continue;
				}

				$data = json_decode($fetchedData);
				$insertCount = 0;

				foreach($data as $kill)
				{
					if(isset($kill->_stringValue))
						unset($kill->_stringValue);

					$hash = Util::getKillHash(null, $kill);
					$json = json_encode($kill);
					$killID = $kill->killID;
					$source = "zKB Feed Fetch";
					$lastKillTime = $kill->killTime;
					echo "$killID $lastKillTime\n";

					$insertCount += Db::execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) VALUES (:killID, :hash, :source, :kill_json)",
							array(":killID" => $killID, ":hash" => $hash, ":source" => $source, ":kill_json" => $json));
				}

				if ($insertCount == 0) Db::execute("UPDATE zz_feeds SET lastFetchTime = now() WHERE id = :id", array(":id" => $id));
				Db::execute("UPDATE zz_feeds SET lastKillTime = :lastKillTime WHERE id = :id", array(":id" => $id, ":lastKillTime" => $lastKillTime));

				$totalCount += $insertCount;
				CLI::out("Inserted |g|$insertCount|n|/|g|" . sizeof($data) . "|n| kills...");
				Log::log("Inserted $insertCount new kills from $url");

				if($insertCount > 0 && $totalCount < 400)
				{
					CLI::out("|g|Pausing|n| for 15 seconds...");
					sleep(15);
				}
			} while ($insertCount > 0 && $totalCount < 400);
		}

		if ($totalCount > 0)
			CLI::out("Inserted a total of |g|" . number_format($totalCount, 0) . "|n| kills.");
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
