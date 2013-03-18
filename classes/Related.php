<?php

class Related
{
	public static function buildSummary(&$kills, $parameters = array(), $key)
	{
		if ($kills == null || !is_array($kills) || sizeof($kills) == 0) return array();

		$key = "related:$key";
		$mc = Memcached::get($key);
		//if ($mc) return $mc;

		// Determine which entity got on the most killmails
		$typeColumn = "";
		$involvedArray = self::findLosers($kills, $typeColumn = "allianceID");
		if (sizeof($involvedArray) < 2) $involvedArray = self::checkCounts($kills, $typeColumn = "corporationID");
		if (sizeof($involvedArray) < 2) $involvedArray = self::checkCounts($kills, $typeColumn = "characterID");
		// Sort the array
		uasort($involvedArray, "Related::involvedArraySort");
		reset($involvedArray);

		$teamA = array();
		$teamB = array();
		$neutrals = array_keys($involvedArray);
		while (sizeof($neutrals)) {
			$entity = $neutrals[0];
			$currentArray = null;
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
