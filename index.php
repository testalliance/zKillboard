<?php
// Load modules + database stuff (and the config)
require( "init.php" );

// initiate the timer!
$timer = new Timer();

// Start slim
$app = new \Slim\Slim($config);

// Session
session_cache_limiter(false);
session_start();

// Check if the user has autologin turned on
if(!User::isLoggedIn()) User::autoLogin();

// Theme
$viewtheme = null;
if(User::isLoggedIn())
	$viewtheme = UserConfig::get("viewtheme");
$app->config(array("templates.path" => $baseDir."templates/" . ($viewtheme ? $viewtheme : "bootstrap")));

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