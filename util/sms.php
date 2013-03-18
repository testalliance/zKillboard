<?php
require_once( dirname(__FILE__) . "/../init.php" );
$message = array();
$url = "https://twitter.com/eve_kill/status/";
$storageName = "smsLatestID";

$latest = Db::queryField("SELECT contents FROM zz_storage WHERE locker = '$storageName'", "contents", array(), 0);
if ($latest == null) $latest = 0;
$maxID = $latest;

$url = "http://www.bulksms.co.uk:5567/eapi/reception/get_inbox/1/1.1?username=karbowiak&password=29641363&last_retrieved_id=$maxID";
$response = file_get_contents($url);

$msgs = explode("\n", $response);

$cleanMsgs = array();
// Clean it up
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
