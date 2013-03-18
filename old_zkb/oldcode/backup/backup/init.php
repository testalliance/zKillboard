<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

// Basic stuff we need to know
$baseUrl = "http://zkillboard.com";
$baseDir = dirname(__FILE__);

spl_autoload_register("kwautoload");
require_once "config.php";
require_once "eve_tools.php";
require_once "display.php";

$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "zkillboard.com";
$subDomain = str_replace("zkillboard.com", "", $serverName);
if (strlen($subDomain) > 1) $subDomain = substr($subDomain, 0, strlen($subDomain) - 1);

//if (strlen($subDomain) > 1) $baseUrl = str_replace("http://", "http://$subDomain.", $baseUrl);
$baseUrl = "";

$p = processParameters();

function kwautoload($class_name)
{
    $baseDir = dirname(__FILE__);
    $fileName = "$baseDir/classes/$class_name.php";
    if (file_exists($fileName)) {
        require_once $fileName;
        return;
    }
    $fileName = "$baseDir/pages/$class_name.php";
    if (file_exists($fileName)) {
        require_once $fileName;
        return;
    }
}

function processParameters()
{
    global $argv, $argCount, $subDomain;
    $cle = "cli" == php_sapi_name(); // Command Line Execution?
    $p = array();

    if ($cle) {
        foreach ($argv as $arg) {
            if ($argCount >= 0) $p[] = $arg;
        }
        array_shift($p);
    } else {
        $parameters = isset($_GET['p']) ? explode("/", $_GET['p']) : array();
        foreach ($parameters as $param) {
            if (strlen(trim($param)) > 0) $p[] = $param;
        }
    }

	/*if (sizeof($p) == 0 && strlen($subDomain) == 0) {
		$p[] = "date";
		@$p[] = @date("Ymd");
	}*/

    return $p;
}
