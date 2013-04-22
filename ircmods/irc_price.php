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
