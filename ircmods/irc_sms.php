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

class irc_sms implements ircCommand {
	public function getRequiredAccessLevel() {
		return 1;
	}

	public function getDescription() {
		return "Sends an SMS to the person. Usage: |g|.z sms <name> <message>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		global $smsUsername, $smsPassword;
        $smstoname = $parameters[0];
        
        $smsto = Db::queryField("SELECT mobilenumber FROM zz_irc_mobile WHERE name = :name", "mobilenumber", array(":name" => $smstoname));
        if(!$smsto)
            irc_out("|g|Error:|n| User is not added with a phone number, add one with |g| .z addnumber <name> <number>|n|");
        unset($parameters[0]);
        $message = implode(" ", $parameters);
        $message = $message." //".$nick;

        $url = "http://www.bulksms.co.uk/eapi/submission/send_sms/2/2.0";
		$data = "username=".$smsUsername."&password=".$smsPassword."&routing_group=2&message=".urlencode($message)."&repliable=1&msisdn=".$smsto;

        $opts = array("http" =>
            array(
                "method" => "POST",
                "header" => "Content-type: application/x-www-form-urlencoded",
                "content" => $data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        
        if(stristr($result, "IN_PROGRESS"))
            irc_out("|g|SMS is being sent to:|n| $smstoname");
        if(stristr($result, "invalid"))
            irc_out("|r|Error:|n| $result");
		if(stristr($result, "too long"))
			irc_out("|r|Error:|n| $result");
        
	}
    private static function string_to_utf16_hex( $string ) {
        return bin2hex(mb_convert_encoding($string, "UTF-16", "UTF-8"));
    }
    public function isHidden() { return false; }
}
