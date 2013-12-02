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

class Social
{

	public static function findConversations()
	{
		$timer = new Timer();
		$locker = "Social:lastSocialTime";
		$now = time();
		$lastSocialTime = Storage::retrieve($locker, (time() - (24 * 3600)));
		$result = Db::query("select killID, unix_timestamp(insertTime) insertTime from zz_killmails where killID > 0 and processed = 1 and insertTime >= from_unixtime(:last) order by insertTime", array(":last" => $lastSocialTime), 0);
		foreach ($result as $row) {
			$lastSocialTime = $row["insertTime"];
			Social::beSocial($row["killID"]);
		}
		Storage::store($locker, $now);
	}

	public static function beSocial($killID)
	{
		if ($killID < 0) return;
		$ircMin = 5000000000;
		$twitMin = 10000000000;

		// This is an array of characters we like to laugh at :)
		$laugh = array(
				1633218082, // Squizz Caphinator
				924610627, // fr0gOfWar (petllama)
				619471207, // Flyboy
				268946627, // Karbowiak
				179004085, // Peter Powers
				428663616, // HyperBeanie (Beansman)
				);

		$count = Db::queryField("select count(*) count from zz_social where killID = :killID", "count", array(":killID" => $killID), 0);
		if ($count != 0) return;

		// Get victim info
		$victimInfo = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1 and dttm > date_sub(now(), interval 1 day)", array(":killID" => $killID));
		if ($victimInfo == null) return;
		$totalPrice = $victimInfo["total_price"];
		$dttm = $victimInfo["dttm"];
		$time = strtotime($dttm);

		if (!in_array($victimInfo["characterID"], $laugh)) { // If in laugh array, skip the checks
			// Check the minimums, min. price and happened in last 12 hours
			if ($totalPrice < $ircMin) return;
		}

		Info::addInfo($victimInfo);
		$emizeko = Db::queryField("select count(1) count from zz_participants where characterID = 1389468720 and killID = $killID and isVictim = 0", "count");
		if ($emizeko > 0) Log::irc("emizeko strikes again!");

		$url = "https://zkillboard.com/detail/$killID/";
		if ($totalPrice >= $twitMin) $url = Twit::shortenUrl($url);
		$message = "|g|" . $victimInfo["shipName"] . "|n| worth |r|" . Util::formatIsk($totalPrice) . " ISK|n| was destroyed! $url";
		if (strlen($victimInfo["characterName"]) < 25 && $victimInfo["characterID"] != 0) {
			$name = $victimInfo["characterName"];
			if (Util::endsWith($name, "s")) $name .= "'";
			else $name .= "'s";
			$message = "$name $message";
		}

		Db::execute("insert into zz_social (killID) values (:killID)", array(":killID" => $killID));

		Log::irc("$message");
		$message = Log::stripIRCColors($message);

		$twit = "";
		if ($totalPrice >= $twitMin) {
			$message .= " #tweetfleet #eveonline";
			$return = Twit::sendMessage($message);
			$twit = "https://twitter.com/eve_kill/status/" . $return->id;
			Log::irc("Message was also tweeted: |g|$twit");
		}
	}
}
