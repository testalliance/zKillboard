<?php

function killMerge($array1, $type, $array2) {
	foreach($array2 as $element) {
		$killid = $element["killID"];
		if (!isset($array1[$killid])) $array1[$killid] = array();
		$array1[$killid][$type] = $element;
	}
	return $array1;
}

class home extends page {
	private $kills = null;

	public function controllerMidPane($parameters = array()) {
		$kills = kills::getKills($parameters);
		$victims = null;
		$finalBlows = null;
		$killIDS = array();
		if (sizeof($kills)) {
			$imploded = implode(",", $kills);
			$victims = Db::query("select * from zz_participants where killID in ($imploded) and isVictim = 'T'");
			$finalBlows = Db::query("select * from zz_participants where killID in ($imploded) and finalBlow = 1");
			$info = Db::query("select * from zz_kills where killID in ($imploded)");
		}

		$merged = killMerge(array(), "victim", $victims);
		$merged = killMerge($merged, "finalBlow", $finalBlows);
		$merged = killMerge($merged, "info", $info);

		$this->kills = $merged;
	}

	public function viewMidPane() {
		$retValue = "<table>";
		foreach ($this->kills as $kill) {
			if (!isset($kill["victim"]) || !isset($kill["finalBlow"])) continue;
			$retValue .= "
				<tr>
					<td>" . $kill["victim"]["killID"] . "</td>
					<td>" . $kill["victim"]["shipTypeID"] . "</td>
					<td>" . $kill["victim"]["characterID"] . "</td>
					<td>" . $kill["victim"]["shipTypeID"] . "</td>
					<td>" . $kill["finalBlow"]["characterID"] . "</td>
					<td>" . $kill["finalBlow"]["shipTypeID"] . "</td>
				</tr>
			";
		}
		$retValue .= "</table>";
		return $retValue;
	}

}
