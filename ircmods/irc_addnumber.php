<?php

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
