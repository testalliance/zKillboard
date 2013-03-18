<?php

class irc_pc implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Retrieves the Eve-Central ISK value of an item. Usage: |g|.z ec_price <item>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$item = trim(implode(" ", $parameters));
		@$typeID = (int) $item;
		if ($typeID == 0) $typeID = Db::queryField("select typeID from ccp_invTypes where typeName = :name", "typeID", array(":name" => trim(implode(" ", $parameters))));
		$name = Db::queryField("select typeName from ccp_invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID));
		if ($name === null) return "|r|$item is not a valid item.";

		$price = Price::getMarketPrice($typeID, false);
		$jita = Price::getMarketPrice($typeID, true);
		return "($typeID)|g|$name|n|has global price of|g|" . number_format($price, 2) . "|n| and Jita price of|g|" . number_format($jita, 2);
	}
    public function isHidden() { return false; }
}
