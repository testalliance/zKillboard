<?php

$base = dirname(__FILE__);
require_once "$base/../init.php";

// Fix unknown group ID's
$result = Db::query("select distinct shipTypeID, i.groupID from zz_participants p left join ccp_invTypes i on (shipTypeID = i.typeID) where shipTypeID = i.typeID and p.groupID = 0 and shipTypeID != 0");
foreach ($result as $row) {
	$shipTypeID = $row["shipTypeID"];
	$groupID = $row["groupID"];
	Log::log("Updating $shipTypeID to group $groupID");
	$affected = Db::execute("update zz_participants set groupID = $groupID where groupID = 0 and shipTypeID = $shipTypeID");
}

Db::execute("set session wait_timeout = 600");
if (!Util::isMaintenanceMode()) {
	Db::execute("replace into zz_storage values ('maintenance', 'true')");
	Log::log("Maitenance mode engaged");
	sleep(60); // Wait for processes to finish and cleanup
}

Db::execute("truncate zz_stats");

try {
	recalc('faction', 'factionID');
	recalc('alli', 'allianceID');
	recalc('corp', 'corporationID');
	recalc('pilot', 'characterID');
	recalc('group', 'groupID');
	recalc('ship', 'shipTypeID');
	recalc('system', 'solarSystemID', false);
	recalc('region', 'regionID', false);
} catch (Exception $e) {
	print_r($e);
}

Db::execute("delete from zz_storage where locker = 'maintenance'");
die();

function recalc($type, $column, $calcKills = true) {
	Log::log("Starting stat calculations for $type");
	echo "$type ";

	Db::execute("drop table if exists zz_stats_temporary");
	Db::execute("
			CREATE TABLE `zz_stats_temporary` (
				`killID` int(16) NOT NULL,
				`groupName` varchar(16) NOT NULL,
				`groupNum` int(16) NOT NULL,
				`groupID` int(16) NOT NULL,
				`points` int(16) NOT NULL,
				`price` decimal(16,2) NOT NULL,
				PRIMARY KEY (`killID`,`groupName`,`groupNum`,`groupID`)
				) ENGINE=InnoDB");

	echo " losses ";
	Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, groupID, points, total_price from zz_participants where $column != 0 and isVictim = 1");
	Db::execute("replace into zz_stats (type, typeID, groupID, lost, pointsLost, iskLost) select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3");

	if ($calcKills) {
		echo " kills ";
		Db::execute("truncate table zz_stats_temporary");
		Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, vGroupID, points, total_price from zz_participants where $column != 0 and isVictim = 0");
		Db::execute("insert into zz_stats (type, typeID, groupID, destroyed, pointsDestroyed, iskDestroyed) (select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3) on duplicate key update destroyed = values(destroyed), pointsDestroyed = values(pointsDestroyed), iskDestroyed = values(iskDestroyed)");
	}

	Db::execute("drop table if exists zz_stats_temporary");

	echo "done!\n";
	Log::log("Finished stat calculations for $type");
}
