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
            return "Error";
        if(!$number)
            return "Error";
        if($name && $number)
        {
            Db::execute("INSERT INTO zz_irc_mobile (name, mobilenumber) VALUES (:name, :number)", array(":name" => $name, ":number" => $number));
            return "Inserted";
        }
	}
    public function isHidden() { return false; }
}
