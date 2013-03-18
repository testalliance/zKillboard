<?php

require_once "../init.php";

$timer = new Timer();
$cleaned = array();

$result = Db::query("select b.hash, a.killID from zz_killmails a left join (select hash, count(*) as count from zz_killmails where processed = 1 group by 1 having count(*) >= 2) as b on (a.hash = b.hash) where b.hash is not null and a.killID < 0", array(), 0);
foreach ($result as $row) {
	$hash = $row["hash"];
	$mKillID = $row["killID"];
	$killID = Db::queryField("select killID from zz_killmails where hash = :hash and killID > 0 limit 1", "killID", array(":hash" => $hash), 0);
	echo "$hash $mKillID $killID\n";
	cleanDupe($mKillID, $killID);
}
echo "Cleaned up " . sizeof($result) . " dupes.\n";


function cleanDupe($mKillID, $killID) {
	Db::execute("update zz_manual_mails set killID = :killID where mKillID = :mKillID",
			array(":killID" => $killID, ":mKillID" => (-1 * $mKillID)));
	Stats::calcStats($mKillID, false); // remove manual version from stats
	Db::execute("update zz_killmails set processed = 2 where killID = :mKillID", array(":mKillID" => $mKillID));
	//Log::ircAdmin("|r| (DupeFinder) Marking $mKillID as duplicate of $killID");
}
