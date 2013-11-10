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

class irc_popular implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "Returns the Top 3 most accessed URIs in the last 15 minutes";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		global $baseAddr;
		$result = Db::query("select uri, count(*) count from zz_online_uri group by 1 order by 2 desc limit 3", array(), 0);
		foreach($result as $row) {
			irc_out("http://$baseAddr" . $row["uri"] . " (|g|" . number_format($row["count"], 0) . "|n| hits)");
		}
	}

    public function isHidden() { return false; }
}
