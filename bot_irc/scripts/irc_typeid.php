<?php

class irc_typeid implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Finds the name or the id of an item. Usage: |g|.z typeid <name> / .z typeid <id>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$item = trim(implode(" ", $parameters));
		if (!is_numeric($item)) $typeID = Db::queryField("select typeID from ccp_invTypes where typeName like :name", "typeID", array(":name" => $item));
		else $typeID = (int) $item;
		$name = Db::queryField("select typeName from ccp_invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID));
		if ($name === null) return "|r|$item|n|is not a valid item.";

		return "|g|$name|n| has typeID of|g|$typeID";
	}
    public function isHidden() { return false; }
}
