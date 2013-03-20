<?php
require_once( "config.php" );

// Autoload Slim + Twig
require( "vendor/autoload.php" );

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