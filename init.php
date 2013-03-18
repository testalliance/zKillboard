<?php
require_once( "config.php" );
require_once( "vendor/mobileDetect/Mobile_Detect.php" );
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