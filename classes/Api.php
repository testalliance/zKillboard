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

/**
 * Various API helper functions for the website
 */
class Api
{

	/**
	 * Checks a key for validity and KillLog access.
	 *
	 * @static
	 * @param $keyID The keyID to be checked.
	 * @param $vCode The vCode to be checked
	 * @return string A message, Success on success, otherwise an error.
	 */
	public static function checkAPI($keyID, $vCode)
	{
		$keyID = trim($keyID);
		$vCode = trim($vCode);
		if ($keyID == "" || $vCode == "")
			return "Error, no keyID and/or vCode";
		$keyID = (int)$keyID;
		if ($keyID == 0) {
			return "Invalid keyID.  Did you get the keyID and vCode mixed up?";
		}

		$pheal = Util::getPheal();
		$pheal = new Pheal($keyID, $vCode);
		try
		{
			$result = $pheal->accountScope->APIKeyInfo();
		}
		catch (Exception $e)
		{
			if (strlen($keyID) > 20)
				return "Error, you might have mistaken keyid for the vcode";
			return "Error: " . $e->getCode() . " Message: " . $e->getMessage();
		}

		$key = $result->key;
		$accessMask = $key->accessMask;
		$keyType = $key->type;
		$hasBits = self::hasBits($accessMask);

		if (!$hasBits) {
			return "Error, key does not have access to killlog, please modify key to add killlog access";
		}
		if ($hasBits) {
			return "success";
		}

	}

	/**
	 * Adds a key to the database.
	 *
	 * @static
	 * @param $keyID
	 * @param $vCode
	 * @param null $label
	 * @return string
	 */
	public static function addKey($keyID, $vCode, $label = null)
	{
		$userID = User::getUserID();
		if ($userID == null) $userID = 0;

		$exists = Db::queryRow("SELECT userID, keyID, vCode FROM zz_api WHERE keyID = :keyID AND vCode = :vCode", array(":keyID" => $keyID, ":vCode" => $vCode), 0);
		if ($exists == null) {
			// Insert the api key
			Db::execute("replace into zz_api (userID, keyID, vCode, label) VALUES (:userID, :keyID, :vCode, :label)", array(":userID" => $userID, ":keyID" => $keyID, ":vCode" => $vCode, ":label" => $label));
		} else if ($exists["userID"] == 0) {
			// Someone already gave us this key anonymously, give it to this user
			Db::execute("UPDATE zz_api SET userID = :userID, label = :label WHERE keyID = :keyID", array(":userID" => $userID, ":label" => $label, ":keyID" => $keyID));
			return "keyID $keyID previously existed in our database but has now been assigned to you.";
		} else {
			return "keyID $keyID is already in the database...";
		}

		$pheal = Util::getPheal();
		$pheal = new Pheal($keyID, $vCode);
		$result = $pheal->accountScope->APIKeyInfo();
		$key = $result->key;
		$keyType = $key->type;

		if ($keyType == "Account") $keyType = "Character";

		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
		elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else $ip = $_SERVER['REMOTE_ADDR'];


		Log::ircAdmin("API: $keyID has been added.  Type: $keyType ($ip)");
		return "Success, your $keyType key has been added.";
	}

	/**
	 * Deletes a key owned by the currently logged in user.
	 *
	 * @static
	 * @param $keyID
	 * @return string
	 */
	public static function deleteKey($keyID)
	{
		$userID = user::getUserID();
		Db::execute("DELETE FROM zz_api_characters WHERE keyID = :keyID", array(":keyID" => $keyID));
		Db::execute("DELETE FROM zz_api WHERE userID = :userID AND keyID = :keyID", array(":userID" => $userID, ":keyID" => $keyID));
		return "$keyID has been deleted";
	}

	/**
	 * Returns a list of keys owned by the currently logged in user.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getKeys($userID)
	{
		$userID = user::getUserID();
		$result = Db::query("SELECT keyID, vCode, label, lastValidation, errorCode FROM zz_api WHERE userID = :userID order by keyID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of charactery keys.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getCharacterKeys($userID)
	{
		$result = Db::query("select c.* from zz_api_characters c left join zz_api a on (c.keyID = a.keyID) where a.userID = :userID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of the characters assigned to this user.
	 *
	 * @static
	 * @return array
	 */
	public static function getCharacters($userID)
	{
		$db = Db::query("SELECT characterID FROM zz_api_characters c left join zz_api a on (c.keyID = a.keyID) where userID = :userID", array(":userID" => $userID), 0);
		$results = Info::addInfo($db);
		return $results;
	}

	/**
	 * Tests the access mask for KillLog access
	 *
	 * @static
	 * @param $accessMask
	 * @return bool
	 */
	private static function hasBits($accessMask)
	{
		return ((int)($accessMask & 256) > 0);
	}
}
