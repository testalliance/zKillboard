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

$base = dirname(__FILE__);
require_once "$base/../init.php";

Db::execute("drop table if exists zz_ranks_temporary");
Db::execute("create table zz_ranks_temporary like zz_ranks");

$types = array("faction", "alli", "corp", "pilot", "ship", "group", "system", "region");
$indexed = array();

foreach($types as $type) {
	Log::log("Calcing ranks for $type");
	Db::execute("truncate zz_ranks_temporary");
	$exclude = $type == "corp" ? "and typeID > 1100000" : "";
	Db::execute("insert into zz_ranks_temporary select * from (select type, typeID, sum(destroyed) shipsDestroyed, null sdRank, sum(lost) shipsLost, null slRank, null shipEff, sum(pointsDestroyed) pointsDestroyed, null pdRank, sum(pointsLost) pointsLost, null plRank, null pointsEff, sum(iskDestroyed) iskDestroyed, null idRank, sum(iskLost) iskLost, null ilRank, null iskEff, null overallRank from zz_stats where type = '$type' $exclude group by type, typeID) as f");

	if ($type == "system" or $type == "region") {
		Db::execute("update zz_ranks_temporary set shipsDestroyed = shipsLost, pointsDestroyed = pointsLost, iskDestroyed = iskLost");
		Db::execute("update zz_ranks_temporary set shipsLost = 0, pointsLost = 0, iskLost = 0");
	}

	// Calculate efficiences
	Db::execute("update zz_ranks_temporary set shipEff = (100*(shipsDestroyed / (shipsDestroyed + shipsLost))), pointsEff = (100*(pointsDestroyed / (pointsDestroyed + pointsLost))), iskEff = (100*(iskDestroyed / (iskDestroyed + iskLost)))");

	// Calculate Ranks for each type
	$rankColumns = array();
	$rankColumns[] = array("shipsDestroyed", "sdRank", "desc");
	$rankColumns[] = array("shipsLost", "slRank", "asc");
	$rankColumns[] = array("pointsDestroyed", "pdRank", "desc");
	$rankColumns[] = array("pointsLost", "plRank", "asc");
	$rankColumns[] = array("iskDestroyed", "idRank", "desc");
	$rankColumns[] = array("iskLost", "ilRank", "asc");
	foreach($rankColumns as $rankColumn) {
		$typeColumn = $rankColumn[0];
		$rank = $rankColumn[1];
		$rankOrder = $rankColumn[2];

		if (!in_array($typeColumn, $indexed)) {
			$indexed[] = $typeColumn;
			Db::execute("alter table zz_ranks_temporary add index($typeColumn, $rank)");
		}

		Db::execute("insert into zz_ranks_temporary (type, typeID, $rank) (SELECT type, typeID, @rownum:=@rownum+1 AS $rank FROM (SELECT type, typeID FROM zz_ranks_temporary ORDER BY $typeColumn desc, typeID ) u, (SELECT @rownum:=0) r) on duplicate key update $rank = values($rank)");

		$dupRanks = Db::query("select * from (select $typeColumn n, min($rank) r, count(*) c from zz_ranks_temporary where type = '$type' group by 1) f where c > 1");
		foreach($dupRanks as $dupRank) {
			$num = $dupRank["n"];
			$newRank = $dupRank["r"];
echo "$type $typeColumn $num $rank -> $newRank \n";
			Db::execute("update zz_ranks_temporary set $rank = $newRank where $typeColumn = $num and type = '$type'");
		}
	}

	// Overall ranking
	Db::execute("update zz_ranks_temporary set shipEff = 0 where shipEff is null");
	Db::execute("update zz_ranks_temporary set pointsEff = 0 where pointsEff is null");
	Db::execute("update zz_ranks_temporary set iskEff = 0 where iskEff is null");
	Db::execute("insert into zz_ranks_temporary (type, typeID, overallRank) (SELECT type, typeID, @rownum:=@rownum+1 AS overallRanking FROM (SELECT type, typeID, (if (shipsDestroyed = 0, 10000000000000, ((shipsDestroyed / (pointsDestroyed + 1)) * (sdRank + idRank + pdRank))) * (1 + (1 - ((shipEff + pointsEff + iskEff) / 300)))) k, (slRank + ilRank + plRank) l from zz_ranks_temporary order by 3, 4 desc, typeID) u, (SELECT @rownum:=0) r) on duplicate key update overallRank = values(overallRank)");
	Db::execute("delete from zz_ranks where type = '$type'");
	Db::execute("insert into zz_ranks select * from zz_ranks_temporary");
}
Db::execute("drop table zz_ranks_temporary");
