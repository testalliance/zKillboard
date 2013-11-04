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

class irc_online implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Get a count of unique IP addresses in the last 5 minutes";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		Db::execute("delete from zz_online where dttm < date_sub(now(), interval 5 minute)");
		$count = Db::queryField("select count(*) count from zz_online", "count", array(), 1);
		irc_out("|g|$count|n| unique IP addresses in the last 5 minutes");
	}
	public function isHidden() { return true; }
}
