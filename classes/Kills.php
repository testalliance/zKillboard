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
class Kills
{
	public static function getKills($parameters = array(), $allTime = true)
	{
		$tables = array();
		$orWhereClauses = array();
		$andWhereClauses = array();
		Filters::buildFilters($tables, $orWhereClauses, $andWhereClauses, $parameters, $allTime);

		$tables = array_unique($tables);
		if (sizeof($tables) == 0) $tables[] = "zz_participants p";

		if (sizeof($tables) == 2) $tablePrefix = "k";
		else $tablePrefix = substr($tables[0], strlen($tables[0]) - 1, 1);

		$query = "select distinct ${tablePrefix}.killID from ";
		$query .= implode(" left join ", array_unique($tables));
		if (sizeof($tables) == 2) $query .= " on (k.killID = p.killID) ";
		if (sizeof($andWhereClauses) || sizeof($orWhereClauses)) {
			$query .= " where ";
			if (sizeof($orWhereClauses) > 0) {
				$andOr = array_key_exists("combined", $parameters) && $parameters["combined"] == true ? " or " : " and ";
				$query .= " ( " . implode($andOr, $orWhereClauses) . " ) ";
				if (sizeof($andWhereClauses)) $query .= " and ";
			}
			if (sizeof($andWhereClauses)) $query .= implode(" and ", $andWhereClauses);
		}

		$limit = array_key_exists("limit", $parameters) ? (int)$parameters["limit"] : 25;
		$page = array_key_exists("page", $parameters) ? (int)$parameters["page"] : 1;
		$offset = ($page - 1) * $limit;

		$orderBy = array_key_exists("orderBy", $parameters) ? $parameters["orderBy"] : "${tablePrefix}.dttm";
		$orderDirection = array_key_exists("orderDirection", $parameters) ? $parameters["orderDirection"] : "desc";
		$query .= " order by $orderBy $orderDirection limit $offset, $limit";

		$cacheTime = array_key_exists("cacheTime", $parameters) ? (int)$parameters["cacheTime"] : 120;
		$cacheTime = max(120, $cacheTime);
		if (array_key_exists("log", $parameters)) Db::log($query, array());
		$kills = Db::query($query, array(), $cacheTime);
		$merged = Kills::getKillsDetails($kills);
		return $merged;
	}

	public static function getKillsDetails($kills)
	{
		$merged = array();
		$killIDS = array();

		foreach ($kills as $kill) {
			$killIDS[] = $kill["killID"];
			$merged[$kill["killID"]] = array();
		}

		if (sizeof($killIDS)) {
			$imploded = implode(",", $killIDS);
			$victims = Db::query("select * from zz_participants where killID in ($imploded) and isVictim = 1", array(), 300);
			$finalBlows = Db::query("select * from zz_participants where killID in ($imploded) and finalBlow = 1", array(), 300);
			$info = $victims;
			$merged = Kills::killMerge($merged, "victim", $victims);
			$merged = Kills::killMerge($merged, "finalBlow", $finalBlows);
			$merged = Kills::killMerge($merged, "info", $info);
		}
		return $merged;
	}

	private static function killMerge($array1, $type, $array2)
	{
		foreach ($array2 as $element) {
			$killid = $element["killID"];
			Info::addInfo($element);
			if (!isset($array1[$killid])) $array1[$killid] = array();
			$array1[$killid][$type] = $element;
		}
		return $array1;
	}

	public static function getKillDetails($killID)
	{
		$victim = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID));
		$kill = $victim;
		$involved = Db::query("select * from zz_participants where killID = :killID and isVictim = 0 order by damage desc", array(":killID" => $killID));
		$items = Db::query("select * from zz_items where killID = :killID order by insertOrder", array(":killID" => $killID));

		Info::addInfo($kill);
		Info::addInfo($victim);
		$infoInvolved = array();
		$infoItems = array();
		foreach ($involved as $i) $infoInvolved[] = Info::addInfo($i);
		unset($involved);
		foreach ($items as $i) $infoItems[] = Info::addInfo($i);
		unset($items);

		return array("info" => $kill, "victim" => $victim, "involved" => $infoInvolved, "items" => $infoItems);
	}

	public static function mergeKillArrays($array1, $array2, $maxSize, $key, $id)
	{
		$maxSize = max(0, $maxSize);
		$resultArray = array_diff_key($array1, $array2) + $array2;
		while (sizeof($resultArray) > $maxSize) array_pop($resultArray);
		foreach ($resultArray as $killID => $kill) {
			if (!isset($kill["victim"])) continue;
			$victim = $kill["victim"];
			if ($victim[$key] == $id) $kill["displayAsLoss"] = true;
			$resultArray[$killID] = $kill;
		}
		return $resultArray;
	}
}
