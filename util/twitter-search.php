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
$message = array();
$url = "https://twitter.com/eve_kill/status/";
$storageName = "twitterSearchLatestID";

$latest = Db::queryField("SELECT contents FROM zz_storage WHERE locker = '$storageName'", "contents", array(), 0);
if ($latest == null) $latest = 0;
$maxID = $latest;
$twitter = Twit::findMessages(25);

foreach ($twitter as $status) {
	$text = (array) $status->text;
	$createdAt = (array) $status->created_at;
	$postedBy = (array) $status->user->name;
	$screenName = (array) $status->user->screen_name;
	$id = (int) $status->id;

    if($screenName[0] == "eve_kill") continue;
	if ($id <= $latest) continue;
	$maxID = max($id, $maxID);

	if (strpos($text[0], "@eve_kill") !== false) continue;

	$message = array("message" => $text[0], "postedAt" => $createdAt[0], "postedBy" => $postedBy[0], "screenName" => $screenName[0], "url" => $url.$id[0]);
	$msg = "Twitter: |g|" . $message["postedBy"] . "|n| (|g|@". $screenName[0] ."|n|) / |g|" . date("Y-m-d H:i:s", strtotime($message["postedAt"])) . " Message:|n| " . $message["message"];
	Log::irc($msg, "");
}
if (sizeof($twitter)) {
	Db::execute("INSERT INTO zz_storage (contents, locker) VALUES (:contents, :locker) ON DUPLICATE KEY UPDATE contents = :contents", array(":locker" => $storageName, ":contents" => $maxID));
}
