<?php
// Load modules + database stuff (and the config)
require( "init.php" );

// Fire up the session! (Yes this isn't pretty, but for whatever reason this makes the sessions work properly across multiple domains..)
session_cache_limiter(false);
$session_id_name = md5("session_id");
if(isset($_COOKIE[$session_id_name]))
	session_id($_COOKIE[$session_id_name]);
session_start();
if(!isset($_COOKIE[$session_id_name]))
	setcookie($session_id_name, session_id(), 0, "/", ".".$baseAddr); //Expires in 30 days


// initiate the timer!
$timer = new Timer();

// Check if the user has autologin turned on
if(!User::isLoggedIn()) User::autoLogin();

// Theme
$viewtheme = null;
if(User::isLoggedIn())
	$viewtheme = UserConfig::get("viewtheme");
$config["templates.path"] = $baseDir."templates/" . ($viewtheme ? $viewtheme : "bootstrap");

// Start slim and load the config from the config file
$app = new \Slim\Slim($config);

// Error handling
$app->error(function (\Exception $e) use ($app){
    include ( "view/error.php" );
});

// Determine subdomain
$restrictedSubDomains = array("www", "email", "mx", "ipv6", "blog", "forum", "cdn", "content", "static", "api");
$serverName = @$_SERVER["SERVER_NAME"];
$subDomain = Util::endsWith($serverName, ".zkillboard.com") ? str_replace(".zkillboard.com", "", $serverName) : null;
if (in_array($subDomain, $restrictedSubDomains) || !Util::isValidSubdomain($subDomain)) {
	header("Location: http://zkillboard.com" . @$_SERVER["REQUEST_URI"]); 
}

// Load the routes - always keep at the bottom of the require list ;)
include( "routes.php" );

// Load twig stuff
include( "twig.php" );

// Run the thing!
$app->run();
