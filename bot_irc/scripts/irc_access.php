<?php

class irc_access implements ircCommand {
	public function getRequiredAccessLevel() {
		return 6;
	}

	public function getDescription() {
		return "Lets you see why a user had their access revoked, and unrevoke it. Or simply lets you revoke a users access. Usage: |g|.z access info/revoke/unrevoke <user>|n| Example: |g|.z access revoke Nick Reason|n| / |g|.z access unrevoke Nick|n| / |g|.z access info Nick|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$cmd = $parameters[0];
		unset($parameters[0]);
		$username = $parameters[1];
		unset($parameters[1]);
		$check = Db::queryField("SELECT username FROM zz_users WHERE username = :username", "username", array(":username" => $username));
		
		if(!$check)
		{
			$username = $username." ".$parameters[2];
			unset($parameters[2]);
			$check = Db::queryField("SELECT username FROM zz_users WHERE username = :username", "username", array(":username" => $username));
		}
		
		if(!$check)
			return "User not found";
		else
			$username = $check;
		
		if($cmd == "info")
		{
			$info = Db::queryField("SELECT revoked_reason FROM zz_users WHERE username = :username", "revoked_reason", array(":username" => $username));
			if($info)
				return "Users access has been revoked, reason: ".$info;
			else
				return "Users access has not been revoked";
		}
		elseif($cmd == "revoke")
		{
			$reason = implode(" ", $parameters);
			Db::execute("UPDATE zz_users SET revoked = 1 WHERE username = :username", array(":username" => $username));
			Db::execute("UPDATE zz_users SET revoked_reason = :reason WHERE username = :username", array(":username" => $username, ":reason" => $reason));
			return "Users access has been revoked";
		}
		elseif($cmd == "unrevoke")
		{
			Db::execute("UPDATE zz_users SET revoked = 0 WHERE username = :username", array(":username" => $username));
			Db::execute("UPDATE zz_users SET revoked_reason = '' WHERE username = :username", array(":username" => $username));
			return "Users access has been unrevoked";
		}
		else
			return "Please specify either info, revoke or unrevoke.";
	}
    public function isHidden() { return false; }
}
