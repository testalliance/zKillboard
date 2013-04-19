<?php
class User
{
	public static function setLogin($username, $password, $autoLogin)
	{
		global $cookie_name, $cookie_time, $baseAddr, $app;
		$hash = Password::genPassword($password);
		if ($autoLogin) {
			$val = $username."/".hash("sha256", $username.$hash.time());
			Db::execute("UPDATE zz_users SET autoLoginHash = :autoLoginHash WHERE username = :username", array(":username" => $username, ":autoLoginHash" => $val));
			$app->setEncryptedCookie($cookie_name, $val, time() + $cookie_time, "/", $baseAddr);
		}
		$_SESSION["loggedin"] = $username;
		return true;
	}

	public static function setLoginHashed($username, $hash)
	{
		global $cookie_name, $cookie_time, $baseAddr, $app;
		$val = $username."/".hash("sha256", $username.$hash.time());
		Db::execute("UPDATE zz_users SET autoLoginHash = :autoLoginHash WHERE username = :username", array(":username" => $username, ":autoLoginHash" => $val));
		$app->setEncryptedCookie($cookie_name, $val, time() + $cookie_time, "/", $baseAddr);
		$_SESSION["loggedin"] = $username;
		return true;
	}
	public static function checkLogin($username, $password)
	{
		$p = Db::query("SELECT username, password FROM zz_users WHERE username = :username", array(":username" => $username));
		if(!empty($p[0]))
		{
			$user = $p[0]["username"];
			$pw = $p[0]["password"];

			if(Password::checkPassword($password, $pw))
				return true;
			return false;
		}
		return false;
	}

	public static function checkLoginHashed($username)
	{
		return Db::queryField("SELECT autoLoginHash FROM zz_users WHERE username = :username", "autoLoginHash", array(":username" => $username), 0);
	}

	public static function autoLogin()
	{
		global $cookie_name, $cookie_time, $app;
		$sessionCookie = $app->getEncryptedCookie($cookie_name);
		if (!empty($sessionCookie)) {
			$cookie = explode("/", $sessionCookie);
			$username = $cookie[0];
			$cookieHash = $cookie[1];
			$hash = self::checkLoginHashed($username, $cookieHash);
			if ($sessionCookie == $hash) {
				self::setLoginHashed($username, $hash);
				return true;
			}
			return false;
		}
		return false;
	}

	public static function isLoggedIn()
	{
		return isset($_SESSION["loggedin"]);
	}

	public static function getUserInfo()
	{
		if (isset($_SESSION["loggedin"])) {
			$id = Db::query("SELECT id, username, email, dateCreated, admin, moderator, revoked FROM zz_users WHERE username = :username", array(":username" => $_SESSION["loggedin"]), 1);
			return @array("id" => $id[0]["id"], "username" => $id[0]["username"], "admin" => $id[0]["admin"], "moderator" => $id[0]["moderator"], "email" => $id[0]["email"], "revoked" => $id[0]["revoked"], "dateCreated" => $id[0]["dateCreated"]);
		}
		else
			return null;
	}

	public static function getUserID()
	{
		if (isset($_SESSION["loggedin"])) {
			$id = Db::queryField("SELECT id FROM zz_users WHERE username = :username", "id", array(":username" => $_SESSION["loggedin"]), 1);
			return $id;
		}
		return null;
	}

	public static function isModerator()
	{
		$info = self::getUserInfo();
		return $info["moderator"] == 1;
	}

	public static function isAdmin()
	{
		$info = self::getUserInfo();
		return $info["admin"] == 1;
	}
	
	public static function isRevoked()
	{
		$info = self::getUserInfo();
		return $info["revoked"] == 1;
	}
	
	public static function getRevokeReason()
	{
		$reason = Db::queryField("SELECT revoked_reason FROM zz_users WHERE id = :id", "revoked_reason", array(":id" => self::getUserID()));
		return $reason;
	}
}
