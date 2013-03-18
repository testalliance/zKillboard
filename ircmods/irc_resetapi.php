<?php

class irc_resetapi implements ircCommand {
	public function getRequiredAccessLevel() {
		return 1;
	}

	public function getDescription() {
		return "Resets any api's associated with the entity given. Usage: |g|.z resetapi <keyID> / .z resetapi Fly8oy|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$keyIDs = array();
		$entity = implode(" ", $parameters);
		if ($entity == "all") {
            Db::execute("update zz_api_characters set lastChecked = 0, cachedUntil = 0, maxKillID = 0, errorCode = 0 where errorCode != 0");
            Db::execute("update zz_api set lastValidation = 0, errorCode = 0 where errorCode != 0");
			irc_out("|g|All API keys have been reset.");
			return;
		}
		if (sizeof($parameters) == 1 && ((int) $parameters[0])) {
			$keyIDs[] = (int) $parameters[0];
		}
		else {
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
				foreach($keys as $key) $keyIDs[] = $key["keyID"];
			} else {
				$corpID = Db::queryField("select corporationID from zz_corporations where name = :s order by memberCount desc",
						"corporationID", array(":s" => $entity));
				if ($corpID !== null && $corpID > 0) $corps[] = $corpID;
			}

			if (sizeof($corps)) {
				$keys = Db::query("select distinct keyID from zz_api_characters where isDirector = 'T' and corporationID in (" . implode(",", $corps) . ")");
				foreach($keys as $key) $keyIDs[] = $key["keyID"];
			}
		}
		if (sizeof($keyIDs) == 0) {
			irc_out("|r|Unable to locate any keys associated with $entity |n|");
		} else {
			$keyIDs = array_unique($keyIDs);
			sort($keyIDs);
			$key = sizeof($keyIDs) == 1 ? "keyID" : "keyIDs";
			$keys = implode(", ", $keyIDs);
			Db::execute("update zz_api_characters set lastChecked = 0, cachedUntil = 0, maxKillID = 0 where keyID in ($keys)");
			Db::execute("update zz_api set lastValidation = 0, errorCode = 0 where keyID in ($keys)");
			if (sizeof($keyIDs)) irc_out("Resetting $key: $keys");
		}
	}
    public function isHidden() { return false; }
}
