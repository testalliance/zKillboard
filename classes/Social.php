<?php

class Social
{

	public static function findConversations()
	{
		$timer = new Timer();
		$maxTime = 65 * 1000;
		while ($timer->stop() < $maxTime) {
			$lastSocialTime = Storage::retrieve("Social:lastSocialTime", (time() - (12 * 3600)));
			$result = Db::query("select killID, unix_timestamp(insertTime) insertTime from zz_killmails where processed = 1 and insertTime >= from_unixtime(:last) order by killID", array(":last" => $lastSocialTime), 0);
			foreach ($result as $row) {
				$lastSocialTime = max($lastSocialTime, $row["insertTime"]);
				Social::beSocial($row["killID"]);
			}
			Storage::store("Social:lastSocialTime", $lastSocialTime);
			sleep(3);
		}
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
		$victimInfo = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID));
		$totalPrice = $victimInfo["total_price"];
		$unixTime = $victimInfo["unix_timestamp"];

		if (!in_array($victimInfo["characterID"], $laugh)) { // If in laugh array, skip the checks
			// Check the minimums, min. price and happened in last 12 hours
			if ($totalPrice < $ircMin) return;
			if ($unixTime < (time() - (12 * 3600))) return;
		}

		Info::addInfo($victimInfo);
		$emizeko = Db::queryField("select count(1) count from zz_participants where characterID = 1389468720 and killID = $killID and isVictim = 0", "count");
		if ($emizeko > 0) Log::irc("emizeko strikes again!");

		$url = "https://zkillboard.com/detail/$killID/";
		if ($totalPrice >= $twitMin) $url = Twit::shortenUrl($url);
		$message = "|g|" . $victimInfo["shipName"] . "|n| worth |r|" . Util::formatIsk($totalPrice) . " ISK|n| was destroyed! $url";
		if (strlen($victimInfo["characterName"]) < 25) {
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
			$twit = Twit::sendMessage($message);
			$twit = "https://twitter.com/eve_kill/status/$twit";
			Log::irc("Message was also tweeted: |g|$twit");
		}
	}
}
