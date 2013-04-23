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

require_once( dirname(__FILE__) . "/../init.php" );
Bin::set("WaitForProcessing", false);

$count = Db::queryField("select count(*) count from zz_killmails where processed = 0", "count", array(), 0);
if ($count > 500) return;

$eveKillURL = "http://eve-kill.net/mailexport.php?";

// Pull the latest manual postings
Db::execute("insert ignore into zz_manual_mail_list select kll_id, 0 from killboard.kb3_mails where kll_external_id = 0 or kll_external_id is null and kll_modified_time >= date_sub(now(), interval 1 hour)");

$result = Db::query("select eveKillID from zz_manual_mail_list where processed = 0 order by eveKillID desc limit 1000", array(), 0);
foreach($result as $row)
{
	$currentID = $row["eveKillID"];
	Db::execute("update zz_manual_mail_list set processed = -2 where eveKillID = $currentID");
	//echo "\n$currentID: ";

	$vars = "&kll_id=" . $currentID;
	$url = $eveKillURL . $vars;
	$killmail = file_get_contents($url);

	if ($killmail == "error" || $killmail == "The specified kill ID is not valid." || $killmail == "") {
		//echo "$killmail\n";
		continue;
	}

	$id = Parser::parseRaw($killmail, "EveKill");

	if (isset($id["success"]) && $id["success"] < 0 ) {
		$mKillID = -1 * $id["success"];
		Db::execute("UPDATE zz_manual_mails SET eveKillID = :evekillid WHERE mKillID = :killID",
				array(":evekillid" => $currentID, ":killID" => $mKillID));
		//echo "Posted with " . $id["success"] . " at http://zkillboard.com/detail/" . $id["success"] . "/\n";
		Db::execute("update zz_manual_mail_list set processed = 1 where eveKillID = $currentID");
		continue;
	}
	elseif (isset($id["error"]) || (isset($id["success"]) && $id["success"] == 0))
	{
		// There are errors, echo them out and carry on
		//echo " has the following errors:\n";
		if (isset($id["error"])) foreach ($id["error"] as $error) {
			//echo "Error: $error \n";
			Db::execute("insert ignore into zz_manual_mail_fails values (:id, :error)", array(":id" => $currentID, ":error" => $error));
		}
		Db::execute("update zz_manual_mail_list set processed = -1 where eveKillID = $currentID");
		continue;
	}
	elseif (isset($id["dupe"]))
	{
		//echo " is a duplicate...\n";
		Db::execute("update zz_manual_mail_list set processed = 1 where eveKillID = $currentID");
		continue;
	}
	else {
		print_r($id);
		die("Some unknown bug just happened..\n");
	}
}
if (sizeof($result)) Log::log("Posted " . sizeof($result) . " manual mails from EveKill");

