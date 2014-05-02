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

if (!isset($detail)) $detail = "ships";
$split = explode("-", $detail);
$detail = $split[0];
$id = isset($split[1]) ? $split[1] : 0;

try {
	switch ($detail) {
		case "ships":
			$topShips = Db::query("select * from (select shipTypeID, sum(total) sum from zz_dna where total >= 5 group by shipTypeID) as foo where sum >= 100 order by sum desc limit 25");
			Info::addInfo($topShips);
			$data = array();
			$data["topShips"] = $topShips;

			$app->render("poploss.html", array("data" => $data));
			break;
		case "top":
		case "ship":
			if ($id == 0) {
				$topShips = Db::query("select * from (select shipTypeID, sum(total) sum, killID from zz_dna group by shipTypeID) as foo where sum >= 5 order by sum desc limit 25");
				$topShip = $topShips[0];
				$typeID = $topShip["shipTypeID"];
			} else $typeID = $id;
			$shipFits = Db::query("select * from (select rowID, shipTypeID, dna, killID, sum(total) total from zz_dna where shipTypeID = :typeID group by shipTypeID, dna) as foo where total >= 5 order by total desc limit 25", array(":typeID" => $typeID));
			Info::addInfo($shipFits);

			$data = array();
			$data["ship"] = $shipFits[0];
			$data["shipFits"] = $shipFits;
			$app->render("poploss-top.html", array("data" => $data));
			break;
		case "kill":
			$dna = Db::queryField("select dna from zz_dna where killID = :id", "dna", array(":id" => $id));
			$md5 = md5($dna);
			$kill = Kills::getKillDetails($id);
			$kill["victim"]["killID"] = 0;
			$fit = Detail::eftarray(md5($id), $kill["items"], $kill["victim"]["characterID"]);
			$extra = array();
			$extra["dnatext"] = Fitting::DNA($kill["items"], $kill["info"]["shipTypeID"]);
			$extra["items"] = Detail::fullCombinedItems(md5($id), $kill["items"], true);
			$extra["slotCounts"] = Info::getSlotCounts($kill["victim"]["shipTypeID"]);
			$extra["fittingwheel"] = $fit;
			$extra["efttext"] = Fitting::EFT($extra["fittingwheel"]);
			$extra["disqusid"] = "poploss-$md5";
			$app->render("poploss-kill.html", array("extra" => $extra, "killdata" => $kill,"flags" => Info::$effectFitToSlot));
			break;
	}
} catch (Exception $ex) {
	header("Content-Type: text/plain");
	print_r($ex);
}
