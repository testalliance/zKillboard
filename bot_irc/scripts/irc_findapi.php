<?php

class irc_findapi implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Finds api's associated with the entity given. Usage: |g|.z findapi <entity>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$keyIDs = array();
		$entity = implode(" ", $parameters);
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
		if (sizeof($keyIDs) == 0) {
			return "|r|Unable to locate any keys associated with $entity |n|";
		} else {
			$keyIDs = array_unique($keyIDs);
			sort($keyIDs);
			$key = sizeof($keyIDs) == 1 ? "key" : "keys";
			$keys = implode(", ", $keyIDs);
			if (sizeof($keyIDs)) return "|g|Following $key found to be associated with $entity:|n| $keys";
		}
	}
    public function isHidden() { return false; }
}
