<?php

class irc_tweet implements ircCommand {
	public function getRequiredAccessLevel() {
		return 4;
	}

	public function getDescription() {
		return "Posts a message to Twitter, as EVE_KILL. Usage: |g|.z tweet <message>|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
        $return = "";
        $url = "https://twitter.com/eve_kill/status/";
        $message = implode(" ", $parameters);
        $pieces = explode(" ", $message);
        foreach($pieces as $piece)
        {
            if(preg_match("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", $piece)) {
                $newsmallurl = Twit::shortenUrl($piece);
                $message = str_replace($piece, $newsmallurl, $message);
            }
        }
        if(strlen($message) > 140)
            irc_out("|r|Message over 140 characters long:|n| " . strlen($message));
        else
        {
            $return = Twit::sendMessage($message);
            if($return)
                irc_out("|g|Message sent:|n| ". $url.$return->id);
            else
                irc_out("|r|Error sending message|n|");
        }
	}
    public function isHidden() { return false; }
}