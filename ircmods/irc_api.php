<?php

class irc_api implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Obtain the status of a certain keyID or a summary of error codes.  Usage: |g| .z api errors|n| or |g|.z api <keyid>|n| or |g| .z api <name of pilot / corp>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$keyIDs = array();
		$entity = implode(" ", $parameters);
		switch ($entity) {
			case "errors":
				$codes = Db::query("select errorCode, count(*) count from zz_api group by 1 order by 1");
				$output = "API base:";
				foreach($codes as $row)	$output .= " |n|| |r|" . $row["errorCode"] . ": |g|" . $row["count"];
				irc_out($output);
				$codes = Db::query("select errorCode, count(*) count from zz_api_characters group by 1 order by 1");
				$output = "API characters:";
				foreach($codes as $row)	$output .= " |n|| |r|" . $row["errorCode"] . ": |g|" . $row["count"];
				irc_out($output);
			case "stats":
				$log = Db::query("select substring(errorCode, 1, 3) error, count(*) count from zz_api_log where requestTime >= date_sub(now(), interval 1 hour) group by 1");
				$sum = 0;
				$errorSum = 0;
				foreach($log as $row) {
					$sum += $row["count"];
					if ($row["error"] !== null) $errorSum += $row["count"];
				}
				$sumFreq = number_format($sum / 3600, 1);
				$errorFreq = number_format($errorSum / 3600, 1);
				$sum = number_format($sum, 0);
				$errorSum = number_format($errorSum, 0);
				irc_out("API Stats 1 Hour: |g|$sum |n|requests with |r|$errorSum |n|errors. Frequency: |g|$sumFreq|n|/s |r|$errorFreq|n|/s");
				return;
		}
		$strIntParam = "$intParam";
		if (sizeof($parameters) == 1 && ((int) $parameters[0]) && strlen($parameters[0]) == strlen($strIntParam)) {
			$keyIDs[] = (int) $parameters[0];
		}
		else {
			if (strlen($entity) == 0) irc_error("Please specify a name or keyID, see help if you are further confused.");
			// Perform a search

			$chars = array();
			$corps = array();

			$charResult = Db::query("select characterID from zz_characters where name = :s", array(":s" => $entity));
			foreach($charResult as $char) $chars[] = $char["characterID"];

			foreach($chars as $charID) {
				$corpID = Db::queryField("select corporationID from zz_participants where characterID = :c order by killID desc limit 1",
						"corporationID", array(":c" => $charID));
				if ($corpID !== null && $corpID > 0) $corps[] = $corpID;
			}

			if (sizeof($chars)) {
				$keys = Db::query("select distinct keyID from zz_api_characters where isDirector = 'F' and characterID in (" . implode(",", $chars) . ")");
				foreach($keys as $key) $keyIDs[] = $key["keyID"] . " (char)";
			} else {
				$corpID = Db::queryField("select corporationID from zz_corporations where name = :s order by memberCount desc",
						"corporationID", array(":s" => $entity));
				if ($corpID !== null && $corpID > 0) $corps[] = $corpID;
			}

			if (sizeof($corps)) {
				$keys = Db::query("select distinct keyID from zz_api_characters where isDirector = 'T' and corporationID in (" . implode(",", $corps) . ")");
				foreach($keys as $key) $keyIDs[] = $key["keyID"] . " (corp)";
			}
		}
		if (sizeof($keyIDs) == 0) irc_error("|r|Unable to locate any keys associated with $entity |n|");

		foreach($keyIDs as $keyID) {
			if ($keyID == 0) irc_error("Please provide a valid keyID.");
			$info = Db::queryRow("select * from zz_api where keyID = :k", array(":k" => $keyID), 0);
			if ($info == null) irc_error("Could not find KeyID $keyID");

			$vCode = $info["vCode"];
			$vCodeShort = substr($vCode, 0, 3) . "...";

			$hasErrors = false;
			$detail = Db::query("select * from zz_api_characters where keyID = :k", array(":k" => $keyID), 0);
			if (sizeof($detail) == 0) {
				$hasErrors = true;
				irc_out("KeyID $keyID has |r|no characters|n| assigned to it.");
			}
			foreach($detail as $row) {
				Info::addInfo($row);
				$type = $row["isDirector"] == "T" ? "corp" : "char";
				$errorCode = $row["errorCode"];
				$hasErrors |= $errorCode > 0;
				@$lastChecked = $row["lastCheckedTime"];
				@$cachedUntil = $row["cachedUntilTime"];
				$details = "";
				if ($channel == "#escadmin") {
					if ($type == "char") $details .= "| " . $row["characterName"] . " (" . $row["characterID"] . ")";
					else $details .= "| " . $row["corporationName"] . " (" . $row["corporationID"] . ")";
				}
				irc_out("KeyID |g|$keyID|n| | Type: $type | Error Code: $errorCode | Last Checked: $lastChecked | Cached Until: $cachedUntil $details");
			}

			if ($hasErrors) {
				$errorCode = $info['errorCode'];
				$lastValidated = $info['lastValidation'];
				$dateAdded = $info['dateAdded'];
				irc_out("KeyID Overview $keyID|n| | $vCodeShort | Error Code: $errorCode | Last Validated: $lastValidated | Added: $dateAdded");
			}
		}
	}
    public function isHidden() { return false; }
}
