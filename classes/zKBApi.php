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
class zKBApi
{
	public static function createKey($userID)
	{
		$sha1 = sha1($userID . time());
		Db::execute("INSERT INTO zz_zkbapi (userID, keyCode) VALUES (:userID, :key)", array(":userID" => $userID, ":key" => $sha1));
	}

	public static function deleteKey($userID, $keyCode)
	{
		Db::execute("DELETE FROM zz_zkbapi WHERE userID = :userID AND keyCode = :keyCode", array(":userID" => $userID, ":keyCode" => $keyCode));
	}

	public static function getKey($userID)
	{
		$result = Db::query("SELECT keyCode, lastAccess, accessLeft, additionalAccess FROM zz_zkbapi WHERE userID = :userID", array(":userID" => $userID), 0);
		return $result;
	}

	public static function accessAllowed($userID)
	{
		return Db::queryField("SELECT accessLeft+additionalAccess as accessAllowed FROM zz_zkbapi WHERE userID = :userID", "accessAllowed", array(":userID" => $userID), 0);
	}

	public static function resetAccess($userID)
	{
		Db::execute("UPDATE zz_zkbapi SET accessCount = 0 WHERE userID = :userID", array(":userID" => $userID));
	}

	public static function incrementAccess($userID)
	{
		Db::execute("UPDATE zz_zkbapi SET accessCount = accessCount+1 WHERE userID = :userID", array(":userID" => $userID));
	}

	public static function accessCount($userID)
	{
		return Db::queryField("SELECT accessCount FROM zz_zkbapi WHERE userID = :userID", "accessCount", array(":userID" => $userID), 0);
	}

	public static function updateAdditionalAccess($userID, $access)
	{
		Db::execute("UPDATE zz_zkbapi SET additionalAccess = :access WHERE userID = :userID", array(":userID" => $userID, ":access" => $access));
	}

	public static function lastAccess($userID)
	{
		return Db::queryField("SELECT lastAccess FROM zz_zkbapi WHERE userID = :userID", "lastAccess", array(":userID" => $userID), 0);
	}

	public static function checkHash($keyCode)
	{
		return Db::queryField("SELECT userID FROM zz_zkbapi WHERE keyCode = :keyCode", "userID", array(":keyCode" => $keyCode), 0);
	}

	public static function accessLog($userID, $urlAccessed, $ip, $userAgent)
	{
		Db::execute("INSERT INTO zz_zkbapi_log (userID, urlAccessed, ip, userAgent) VALUES (:userID, :urlAccessed, :ip, :userAgent)", array(":userID" => $userID, ":urlAccessed" => $urlAccessed, ":userAgent" => $userAgent));
	}
}
