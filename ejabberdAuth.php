<?php

require_once("init.php");
require_once("classes/ejabberd_external_auth.php");

class Authenticator extends EjabberdExternalAuth
{
	protected function authenticate($user, $server, $password)
	{
                $storedpws = Db::query("SELECT password, boshAuth FROM zz_users WHERE username = :username", array(":username" => $user));

                if(isset($storedpws[0]["boshAuth"]))
                        $boshAuth = $storedpws[0]["boshAuth"];
                if(isset($storedpws[0]["password"]))
                        $hashedPw = $storedpws[0]["password"];

                if(isset($boshAuth) && $boshAuth == $password) // Bosh
                        return true;

		$passwordIsCorrect = Password::checkPassword($password, $hashedPw);
		if($passwordIsCorrect)
			return true;
                return false;
	}

	protected function exists($user, $server)
	{
		$userExists = Db::queryField("SELECT username FROM zz_users WHERE username = :username", "username", array(":username" => $user));
		if($userExists)
			return true;
		return false;
	}
}

new Authenticator("/var/log/Authejabberd.log");
