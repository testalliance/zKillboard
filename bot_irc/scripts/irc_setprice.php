<?php

class irc_setprice implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "Sets the price of an item. Usage: |g|.z setprice <typeID> <price>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$typeID = (int) $parameters[0];
		if ($typeID == 0) return "|r|Please provide a valid typeID.";
		@$price = (float) $parameters[1];
		if ($price == 0) return "|r|Please provide a valid price.";

		Price::setPrice($typeID, $price);
		return "|g|$typeID|n| price set to to|g| " . number_format($price, 2) . "|n| ISK (|g|" . Util::formatIsk($price) . "|n|)";
	}
    public function isHidden() { return false; }
}
