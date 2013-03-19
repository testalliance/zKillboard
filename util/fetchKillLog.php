<?php
$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/cron.php";

@$keyID = $argv[1];
@$vCode = $argv[2];
@$isDirector = $argv[3];
@$charID = isset($argv[4]) ? $argv[4] : 0;

if ($keyID == "" || $vCode == "") {
	die("you're missing stuff...\n");
}

try {
	$pheal = Util::getPheal($keyID, $vCode);
	$charCorp = ($isDirector == "T" ? 'corp' : 'char');
	$pheal->scope = $charCorp;
	$result = null;

	// Update last checked
	Db::execute("update zz_api_characters set lastChecked = unix_timestamp() where keyID = :keyID and characterID = :characterID",
			array(":keyID" => $keyID, ":characterID" => $charID));

	if ($isDirector == "T") $result = $pheal->KillLog();
	else $result = $pheal->KillLog(array('characterID' => $charID));

	//Log::log("Fetching $keyID $characterID");
	$cachedUntil = $result->cached_until_unixtime;
	if ($cachedUntil < time()) $cachedUntil = time() + 3600;
	Db::execute("update zz_api_characters set cachedUntil = :cachedUntil, errorCode = '0' where keyID = :keyID and characterID = :characterID",
			array(":cachedUntil" => $cachedUntil, ":keyID" => $keyID, ":characterID" => $charID));

	$file = "/var/log/zkb_killlogs/{$keyID}_{$charID}_0.xml";
	@unlink($file);
	error_log($pheal->xml . "\n", 3, $file);

	$aff = processRawApi($keyID, $charID, $result);
	if ($aff > 0) {
		$keyID = "$keyID";
		while (strlen($keyID) < 8) $keyID = " " . $keyID;
		Log::log("KeyID: $keyID ($charCorp) added $aff kill" . ($aff == 1 ? "" : "s"));
	}
} catch (Exception $ex) {
	if ($ex->getCode() != 119 && $ex->getCode() != 120) 
		Log::log($keyID . " " . $ex->getCode() . " " . $ex->getMessage());
	handleApiException($keyID, $charID, $ex);
	return;
}
