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
