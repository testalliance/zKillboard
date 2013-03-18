<?php
$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/pheal/config.php";
require_once "$base/cron.php";

try {
	$pheal = Util::getPheal();
	$pheal->scope = "eve";
	$errors = $pheal->ErrorList();
	foreach($errors->errors as $error) {
		$errorCode = $error["errorCode"];
		$errorText = $error["errorText"];
		echo "$errorCode $errorText\n";
		$key = "api_error:$errorCode";
		Db::execute("replace into zz_storage (locker, contents) values (:c, :t)", array(":c" => $key, ":t" => $errorText));
	}
} catch (Exception $ex) {
	print_r($ex);
}
