<?php

$base = dirname(__FILE__);
require_once "$base/../init.php";

// Fix unknown group ID's
$result = Db::query("select distinct killID from zz_participants where groupID != vGroupID and isVictim = 1");
foreach ($result as $row) {
	$killID = $row["killID"];
	$shipTypeID = Db::queryField("select shipTypeID from zz_participants where killID = $killID and isVictim = 1", "shipTypeID");
	if ($shipTypeID == 0) continue;
	$groupID = Info::getGroupID($shipTypeID);
	echo "Updating $killID to $groupID\n";
	Db::execute("update zz_participants set vGroupID = $groupID where killID = $killID");
}
echo "done!\n";
