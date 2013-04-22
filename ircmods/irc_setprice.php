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

class irc_setprice implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "Sets the price of an item. Usage: |g|.z setprice <typeID> <price>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		@$typeID = (int) $parameters[0];
		if ($typeID == 0) irc_error("|r|Please provide a valid typeID.");
		@$price = (float) $parameters[1];
		if ($price == 0) irc_error("|r|Please provide a valid price.");

		Price::setPrice($typeID, $price);
		irc_out("|g|$typeID|n| price set to to|g| " . number_format($price, 2) . "|n| ISK (|g|" . Util::formatIsk($price) . "|n|)");
	}
    public function isHidden() { return false; }
}
