<?php

// Forbid prefetching, we don't need greedy browsers killing us
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') {
    header('HTTP/1.1 403 Prefetch Forbidden');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

global $baseURL, $baseDir, $subDomain;
// Basic stuff we need to know
$baseUrl = "http://zkillboard.com";
$baseDir = dirname(__FILE__);

if (strlen($subDomain) > 1) $baseUrl = str_replace("http://", "http://$subDomain.", $baseUrl);

spl_autoload_register("kwautoload");
require_once "config.php";

$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "zkillboard.com";
$subDomain = str_replace("zkillboard.com", "", $serverName);
if (strlen($subDomain) > 1) $subDomain = substr($subDomain, 0, strlen($subDomain) - 1);

global $p;
$p = processParameters();

$directories = array("classes", "zkb");
$module = null;

function kwautoload($class_name)
{
	global $directories, $module;

    $baseDir = dirname(__FILE__);
	foreach ($directories as $directory) {
    	$fileName = "$baseDir/$directory/$class_name.php";
    	if (file_exists($fileName)) {
			$module = $directory;
        	require_once $fileName;
        	return;
   	 	}
    	$fileName = "$baseDir/$directory/classes/$class_name.php";
    	if (file_exists($fileName)) {
			$module = $directory;
        	require_once $fileName;
        	return;
		}
	}
	//header("Location: /");
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
