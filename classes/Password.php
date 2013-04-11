<?php
class Password
{	
	public static function genPassword($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	public static function updatePassword($password)
	{
		$userID = user::getUserID();
		$password = self::genPassword($password);
		Db::execute("UPDATE zz_users SET password = :password WHERE id = :userID", array(":password" => $password, ":userID" => $userID));
		return "Updated password";
	}

	public static function checkPassword($plainTextPassword, $storedPassword)
	{
		if (!password_verify($plainTextPassword, $storedPassword))
			return false;
		return true;
	}
}