<?php
// Include Init
require_once( "init.php" );

// initiate the timer!
$timer = new Timer();

// Starting Slim Framework
$app = new \Slim\Slim($config);

// Session
$session = new zKBSession();
session_set_save_handler($session, true);
session_cache_limiter(false);
session_start();

// Check if the user has autologin turned on
if(!User::isLoggedIn()) User::autoLogin();

// Theme
$viewtheme = null;
if(User::isLoggedIn())
	$viewtheme = UserConfig::get("viewtheme");
if (!is_dir("templates/$viewtheme")) $viewtheme = "bootstrap";
$app->config(array("templates.path" => $baseDir."templates/" . ($viewtheme ? $viewtheme : "bootstrap")));

// Error handling
$app->error(function (\Exception $e) use ($app){
    include ( "view/error.php" );
});

// Load the routes - always keep at the bottom of the require list ;)
include( "routes.php" );

// Load twig stuff
include( "twig.php" );

// Send debug info to chrome logger
if($debug)
{
	ChromePhp::log($_SERVER);
	ChromePhp::log("Cache Used: ". Cache::getClass());
	ChromePhp::log("Queries: ". Db::getQueryCount());
	ChromePhp::log("IP Server sees: ". IP::get());
	ChromePhp::log("Page generation time (Minus queries): ". Util::pageTimer());
}

// Run the thing!
$app->run();
