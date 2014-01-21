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

class Related
{
	public static function buildSummary(&$kills, $parameters = array(), $key)
	{
		if ($kills == null || !is_array($kills) || sizeof($kills) == 0) return array();

		$key = "related:$key";
		$mc = Cache::get($key);
		if ($mc) return $mc;

		// Determine which entity got on the most killmails
		$involvedArray = self::findLosers($kills, $typeColumn = "allianceID");
		//if (sizeof($involvedArray) < 2) $involvedArray = self::checkCounts($kills, $typeColumn = "corporationID");
		//if (sizeof($involvedArray) < 2) $involvedArray = self::checkCounts($kills, $typeColumn = "characterID");
		// Sort the array
		uasort($involvedArray, "Related::involvedArraySort");
		reset($involvedArray);

		$teamA = array();
		$teamB = array();
		$neutrals = array_keys($involvedArray);
		while (sizeof($neutrals)) {
			$entity = $neutrals[0];
			if (!in_array($entity, $teamB) && !in_array($entity, $teamB)) $currentArray = "B";
			else if (in_array($entity, $teamB)) $currentArray = "B";
			else $currentArray = "A";

			if ($currentArray == "A") $teamA[] = $entity;
			else $teamB[] = $entity;

			array_shift($neutrals);
		}
		print_r($teamA);
		print_r($teamB);


		// Determine sides based on who shot the most of who (will probably need to be recursive)

		// Summarize data

		// Add info
		die();

	}

	/**
	 * @param string $typeColumn
	 */
	private static function findLosers($kills, $typeColumn)
	{
		$involvedArray = array();
		foreach ($kills as $kill) {
			$victim = $kill["victim"];
			$killID = $victim["killID"];
			self::addInvolvedEntity($involvedArray, $killID, $victim[$typeColumn]);
		}
		return $involvedArray;
	}

	private static function addInvolvedEntity(&$involvedArray, &$killID, &$entity)
	{
		if ($entity == 0) return;
		if (!isset($involvedArray["$entity"])) $involvedArray["$entity"] = array();
		if (!in_array($killID, $involvedArray["$entity"])) $involvedArray["$entity"][] = $killID;
	}

	public static function involvedArraySort($a, $b)
	{
		return sizeof($a) < sizeof($b);
	}
}
