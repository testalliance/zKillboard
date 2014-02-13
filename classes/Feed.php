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

class Feed
{
	/**
	 * Returns kills in json format according to the specified parameters
	 *
	 * @static
	 * @param array $parameters
	 * @return array
	 */
	public static function getKills($parameters = array())
	{
		$ip = IP::get();

		$userAgent = @$_SERVER["HTTP_USER_AGENT"];

		Log::log("API Fetch: " . $_SERVER["REQUEST_URI"] . " (" . $ip . " / " . $userAgent . ")");
		$tables = array();
		$orWhereClauses = array();
		$andWhereClauses = array();
		Filters::buildFilters($tables, $orWhereClauses, $andWhereClauses, $parameters, true);

		$tables = array_unique($tables);
		//if (sizeof($tables) > 1) throw new Exception("Advanced multi-table searching is currently disabled");
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

		if (array_key_exists("limit", $parameters) && $parameters["limit"] < 200) {
			$limit = $parameters["limit"];
			$offset = 0;
		} else {
			$limit = 200; // Hardcoded, yes. This number should never change. -- Squizz
			$page = array_key_exists("page", $parameters) ? (int)$parameters["page"] : 1;
			$offset = ($page - 1) * $limit;
		}

		$orderDirection = array_key_exists("orderDirection", $parameters) ? $parameters["orderDirection"] : "desc";
		$query .= " order by ${tablePrefix}.dttm $orderDirection limit $offset, $limit";

		$cacheTime = 3600;

		$kills = Db::query($query, array(), $cacheTime);

		return self::getJSON($kills, $parameters);
	}

	/**
	 * Groups the kills together based on specified parameters
	 * @static
	 * @param array $kills
	 * @param array $parameters
	 * @return array
	 */
	public static function getJSON($kills, $parameters)
	{
		$retValue = array();

		foreach ($kills as $kill) {
			$killID = $kill["killID"];
			$jsonText = Db::queryField("select kill_json from zz_killmails where killID = :killID", "kill_json", array(":killID" => $killID));
			$json = json_decode($jsonText, true);
			if (array_key_exists("no-items", $parameters)) unset($json["items"]);
			if (array_key_exists("finalblow-only", $parameters))
			{
				$involved = count($json["attackers"]);
				$json["zkb"]["involved"] = $involved;
				$data = $json["attackers"];
				unset($json["attackers"]);
				foreach($data as $attacker)
					if($attacker["finalBlow"] == "1")
						$json["attackers"][] = $attacker;
			}
			elseif (array_key_exists("no-attackers", $parameters))
			{
				$involved = count($json["attackers"]);
				$json["zkb"]["involved"] = $involved;
				unset($json["attackers"]);
			}

			$retValue[] = json_encode($json);
		}
		return $retValue;
	}
}
