<?php
function grabParameters($parameters, $name) {
		$retValue = isset($parameters[$name]) ? $parameters[$name] : null;
		if ($retValue == null) return $retValue;
		if (!is_array($retValue)) $retValue = array($retValue);
		return $retValue;
}

function buildWhere($table, $column, $array) {
	if ($array == null || sizeof($array) == 0) return "";
	return " $table where $column in (" . implode(",", $array) . ")";
}

class kills {

		public static function getKills($parameters = array()) {
				$query = "select distinct killid from ";
				$base = strlen($query);

				$allis = grabParameters($parameters, "allis");
				$corps = grabParameters($parameters, "corps");
				$pilots = grabParameters($parameters, "pilots");
				$factions = grabParameters($parameters, "factions");

				$participants = "zz_participants";
				$query .= buildWhere($participants, "allianceID", $allis);
				$query .= buildWhere($participants, "characterID", $pilots);
				$query .= buildWhere($participants, "corporationID", $corps);
				$query .= buildWhere($participants, "factionID", $factions);

				if (strlen($query) === $base) $query .= " zz_kills";

				$kills = grabParameters($parameters, "kills");
				$losses = grabParameters($parameters, "losses");

				if ($kills) $query .= " where isVictim = 'F' ";
				else if ($losses) $query .= " where isVictim = 'T' ";

				$query .= " order by killid desc limit 25";

				$kills = Db::query($query);
				$killIDS = array();
				foreach($kills as $kill) $killIDS[] = $kill['killid'];
				return $killIDS;
		}
}
