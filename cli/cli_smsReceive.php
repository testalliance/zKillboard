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

class cli_smsReceive implements cliCommand
{
	public function getDescription()
	{
		return "Receives the latest SMS messages from bulkSMS. |w|Beware! This requires a bulkSMS account AND an IRC bot|n|. Usage: |g|smsReceive";
	}
	
	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters)
	{
		$message = array();
		$storageName = "smsLatestID";

		$latest = Db::queryField("SELECT contents FROM zz_storage WHERE locker = '$storageName'", "contents", array(), 0);
		if ($latest == null) $latest = 0;
		$maxID = $latest;

		global $smsUsername, $smsPassword;
		$url = "http://www.bulksms.co.uk:5567/eapi/reception/get_inbox/1/1.1?username=".$smsUsername."&password=".$smsPassword."&last_retrieved_id=$maxID";

		$response = file_get_contents($url);
		$msgs = explode("\n", $response);

		$cleanMsgs = array();
		foreach ($msgs as $msg) {
			$line = explode("|", $msg);
			if (sizeof($line) >= 6) $cleanMsgs[] = $msg;
		}
		$msgs = $cleanMsgs;

		foreach ($msgs as $msg) {
			$line = explode("|", $msg);
			$id = $line[0];
			$num = $line[1];
			$msg = $line[2];

			$name = Db::queryField("select name from zz_irc_mobile where mobilenumber = :number", "name", array(":number" => $num));
			if ($name != null) $num = $name;

			$maxID = max($maxID, $id);
			
			$out = "SMS from |g|$num|n|: $msg";
			Log::irc($out);
		}
		if (sizeof($msgs)) {
			Db::execute("INSERT INTO zz_storage (contents, locker) VALUES (:contents, :locker) ON DUPLICATE KEY UPDATE contents = :contents", array(":locker" => $storageName, ":contents" => $maxID));
		}
	}
}
