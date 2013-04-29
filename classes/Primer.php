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

class Primer
{
	public static function cachePrimer()
	{
		Db::execute("set session wait_timeout = 120");

		self::storeResult(Db::query("select * from zz_characters", array(), 0), "select name from zz_characters where characterID = :id", ":id", "characterID", "name");
		self::storeResult(Db::query("select * from zz_corporations", array(), 0), "select name from zz_corporations where corporationID = :id", ":id", "corporationID", "name");
		self::storeResult(Db::query("select * from zz_alliances", array(), 0), "select name from zz_alliances where allianceID = :id", ":id", "allianceID", "name");
		self::storeResult(Db::query("select * from ccp_invTypes", array(), 0), "select typeName from invTypes where typeID = :typeID", ":typeID", "typeID", "typeName");
	}

	private static function storeResult($result, $query, $paramName, $keyColumn, $valueColumn)
	{
	    foreach($result as $rowNum=>$row)
	    {
	        $keyValue = $row[$keyColumn];
	        $valueValue = $row[$valueColumn];
	        $params = array("$paramName" => $keyValue);
	        $result = array(array("$valueColumn" => $valueValue));
	        $key = Db::getKey($query, $params);
	        Cache::set($key, $result, 10800);
	    }
	}
}