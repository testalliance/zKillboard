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

$error = "";

if($_POST)
{
	@$keyid = trim($_POST["keyid"]);
	@$vcode = trim($_POST["vcode"]);
	@$killmail = $_POST["killmail"];
	@$killmailurl = $_POST["killmailurl"];
	$label = "";

	// Apikey stuff
	if($keyid || $vcode)
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = Api::addKey($keyid, $vcode, $label);
		}
		else
		{
			$error = $check;
		}
	}

	if ($killmailurl)
	{
		$frag = explode(":", $killmailurl);
		if (sizeof($frag) != 2) die("Invalid Killmail Link");
		$killID = (int) $frag[0];
		if ($killID == 0) die("Invalid Killmail Link");
		$hash = $frag[1];
		if (sizeof($hash) == 0) die("Invalid Killmail Link");
		$i = Db::execute("insert ignore into zz_crest_killmail (killID, hash) values (:killID, :hash)", array(":killID" => $killID, ":hash" => $hash));

		$maxIterations = 1; // TODO Bump this up to 20 when we finally process these links
		$iteration = 0;
		do {
			// Has the kill been processed?
			$processed = Db::queryField("select processed from zz_killmails where killID = :killID", "processed", array(":killID" => $killID));
			if ($processed == 1) $app->redirect("/detail/$killID/");
			else if ($processed == 2) $error = "There was an error processing your killmail.  Please contact support.";
			else if ($processed == 3) $error = "Your mail is an NPC only mail and will not be displayed.";
			//else sleep(1); // TODO uncomment this line when we finally process these links
			$iteration++;
		} while ($iteration < $maxIterations && $error == "");
		// $error = "We waited $maxIterations for the kill to be processed but the server may be busy atm, please wait!";
		$error = "Killmail Link successfully submitted.  It will be processed once the CREST endpoint has become available.";

		global $ip;
		if ($i) Log::ircAdmin("|n|External Killmail link submitted:|g| $killmailurl |n|($ip)"); // Log only if new row
	}

	if($killmail)
	{
		$u = User::getUserInfo();
		if(User::isLoggedIn())
		{
			$return = Parser::parseRaw($killmail, $u["id"]);
			if(isset($return["success"]))
				$app->redirect("/detail/".$return["success"]."/");
			if(isset($return["dupe"]))
				$app->redirect("/detail/".$return["dupe"]."/");
			if(isset($return["error"]))
				$error = $return["error"];
		}
		else
			$error = "Sorry, you need to be logged in to post manual killmails";
	}
}
$app->render("postmail.html", array("message" => $error));
