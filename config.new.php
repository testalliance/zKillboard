<?php
date_default_timezone_set("UTC");

// Database parameters
$dbUser = "";
$dbPassword = "";
$dbName = "";
$dbHost = "";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
$baseAddr = "zkillboard.com";
chdir($baseDir);

// Memcache
$memcacheServer = "127.0.0.1";
$memcachePort = "11211";

// Cookiiieeeee
$cookie_name = "zKB";
$cookie_time = (3600 * 24 * 30); // 30 days

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
	"templates.path" => $baseDir."templates/",
	"mode" => "production",
	"debug" => false,
	"log.enabled" => false
	);
