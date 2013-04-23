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

class irc_addnumber implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Adds a phone number to person. Usage: |g|.z addnumber <name> <number>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
        $name = $parameters[0];
        $number = $parameters[1];
        
        if(!$name)
            irc_out("Error");
        if(!$number)
            irc_out("Error");
        if($name && $number)
        {
            Db::execute("INSERT INTO zz_irc_mobile (name, mobilenumber) VALUES (:name, :number)", array(":name" => $name, ":number" => $number));
            irc_out("Inserted");
        }
	}
    public function isHidden() { return false; }
}
