<?php

class jab_pc implements jabCommand {
	public function getDescription() {
		return "Retrieves the ISK value of an item from EVE-Central. Usage: .pc <item>";
	}

	public function execute($nick, $uhost, $parameters) {
		$item = trim(implode(" ", $parameters));
		@$typeID = (int) $item;
		if ($typeID == 0) $typeID = Db::queryField("select typeID from ccp_invTypes where typeName = :name", "typeID", array(":name" => trim(implode(" ", $parameters))));
		$name = Db::queryField("select typeName from ccp_invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID));
		if ($name === null) return "$item is not a valid item.";

		$price = Price::getMarketPrice($typeID, false);
		$jita = Price::getMarketPrice($typeID, true);
		return "($typeID) $name has global price of " . number_format($price, 2) . " and Jita price of " . number_format($jita, 2);
	}
}
