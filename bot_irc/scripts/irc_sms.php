<?php

class irc_sms implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Sends an SMS to the person. Usage: |g|.z sms <name> <message>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
        $username = "karbowiak";
        $password = "29641363";
        $smstoname = $parameters[0];
        
        $smsto = Db::queryField("SELECT mobilenumber FROM zz_irc_mobile WHERE name = :name", "mobilenumber", array(":name" => $smstoname));
        if(!$smsto)
            return "|g|Error:|n| User is not added with a phone number, add one with |g| .z addnumber <name> <number>|n|";
        unset($parameters[0]);
        $message = implode(" ", $parameters);
        $message = $message." //".$nick;

        $url = "http://www.bulksms.co.uk/eapi/submission/send_sms/2/2.0";
		$data = "username=".$username."&password=".$password."&routing_group=2&message=".urlencode($message)."&repliable=1&msisdn=".$smsto;

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
            return "|g|SMS is being sent to:|n| $smstoname";
        if(stristr($result, "invalid"))
            return "|r|Error:|n| $result";
		if(stristr($result, "too long"))
			return "|r|Error:|n| $result";
        
	}
    private static function string_to_utf16_hex( $string ) {
        return bin2hex(mb_convert_encoding($string, "UTF-16", "UTF-8"));
    }
    public function isHidden() { return false; }
}
