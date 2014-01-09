<?php

if(php_sapi_name() != "cli")
    die("This is a cli script!");

if(!extension_loaded('pcntl'))
    die("This script needs the pcntl extension!");

$base = __DIR__;
require_once( "config.php" );

if($debug)
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// vendor autoload
require( "vendor/autoload.php" );

// zkb class autoloader
spl_autoload_register("zkbautoload");

function zkbautoload($class_name)
{
    $baseDir = dirname(__FILE__);
    $fileName = "$baseDir/classes/$class_name.php";
    if (file_exists($fileName))
    {  
        require_once $fileName;
        return;
    }
}

$baseDir = "/var/killboard/mails";
@mkdir($baseDir);

$count = 0;
$killIDs = Db::query("select killID from zz_killid where writ = 0", array(), 0);
foreach ($killIDs as $row) {
	$count++;
	$killID = $row["killID"];
	$id = $killID;
	$botDir = abs($id % 1000);
	while (strlen("$botDir") < 3) $botDir = "0" . $botDir;
	$id = (int) $id / 1000;
	$midDir = abs($id % 1000);
	while (strlen("$midDir") < 3) $midDir = "0" . $midDir;
	$id = (int) $id / 1000;
	$topDir = $id % 1000;

	@mkdir("$baseDir/$topDir");
	@mkdir("$baseDir/$topDir/$midDir");
	$file = "$baseDir/$topDir/$midDir/$killID.txt.gz";

	//echo "$killID $file\n";
	$json = Db::queryField("select kill_json from zz_killmails where killID = :killID", "kill_json", array(":killID" => $killID), 0);
	$fp = gzopen($file, "w9");
	gzwrite($fp, $json);
	gzclose($fp);
	Db::execute("update zz_killid set writ = 1 where killID = :killID", array(":killID" => $killID));
	if ($count % 1000 == 0 ) echo ".";
}
echo "\n";
