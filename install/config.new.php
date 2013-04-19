<?php
date_default_timezone_set("UTC");

// Database parameters
$dbUser = "%dbuser%";
$dbPassword = "%dbpassword%";
$dbName = "%dbname%";
$dbHost = "%dbhost%";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
$baseAddr = "%baseaddr%";
chdir($baseDir);

// Logfile
$logfile = "%logfile%";

// Memcache
$memcacheServer = "%memcache%";
$memcachePort = "%memcacheport%";

// Pheal
$phealCacheLocation = "%phealcachelocation%";

// Cookiiieeeee
$cookie_name = "zKB";
$cookie_time = (3600 * 24 * 30); // 30 days
$cookie_secret = "%cookiesecret%";

// Stomp
$stompServer = "";
$stompUser = "";
$stompPassword = "";

// CloudFlare
$cfUser = "";
$cfKey = "";

// Email stuff
$emailsmtp = "";
$emailusername = "";
$emailpassword = "";
$sentfromemail = "";
$sentfromdomain = "";

// Twitter
$consumerKey = "";
$consumerSecret = "";
$accessToken = "";
$accessTokenSecret = "";

// Slim config
// to enable log, add "log.writer" => call after "log.enabled" => true, - you might have to load it in index after init has run and do $config["log.writer"] = call;
$config = array(
	"mode" => "production",
	"debug" => false,
	"log.enabled" => false,
	"cookies.secret_key" => $cookie_secret
	);
