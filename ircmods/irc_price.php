<?php

class irc_price implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Retrieves the ISK value of an item. Usage: |g|.z price <item>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$item = trim(implode(" ", $parameters));
		@$typeID = (int) $item;
		if ($typeID == 0) $typeID = Db::queryField("select typeID from ccp_invTypes where typeName = :name", "typeID", array(":name" => $item));
		$name = Db::queryField("select typeName from ccp_invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID));
		if ($name === null) irc_error("|r|$item is not a valid item.");

		$price = Price::getItemPrice($typeID);
		irc_out("($typeID) |g|$name|n| has price of |g|" . number_format($price, 2));
	}
    public function isHidden() { return false; }
}
