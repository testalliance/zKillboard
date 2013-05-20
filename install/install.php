<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$base = dirname(__FILE__);
require_once "$base/../init.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

// Force all warnings into errors
set_error_handler("exception_error_handler");

if (file_exists("$base/../config.php")) {
	CLI::out("|r|Your config.php is already setup, if you want to reinstall please delete it.", true);
}

CLI::out("We will prompt you with a few questions.  If at any time you are unsure and want to
back out of the installation hit |g|CTRL+C.|n|

|g|Questions will always have a default answer specified in []'s.  Example:
What is 1+1? [2]|n|

Hitting enter will let you select the default answer.

Some database questions:");

$settings = array();

// Database
$settings["dbuser"] = CLI::prompt("Database username?", "zkillboard");
$settings["dbpassword"] = CLI::prompt("Database password?", "zkillboard");
$settings["dbname"] = CLI::prompt("Database name?", "zkillboard");
$settings["dbhost"] = CLI::prompt("Database server?", "localhost");

// Memcache
$settings["memcache"] = "";
$settings["memcacheport"] = "";

$memc = CLI::prompt("|g|Do you have memcached installed?|n|", "yes");
if($memc == "yes")
{
	$settings["memcache"] = CLI::prompt("Memcache server?", "localhost");
	$settings["memcacheport"] = CLI::prompt("Memcache port?", "11211");
}

// Pheal cache
CLI::out("|g|It is highly recommended you find a good location other than the default for these files.|n|");
$settings["phealcachelocation"] = CLI::prompt("Where do you want to store Pheal's cache files?", "/tmp/");

// Server addr
CLI::out("What is the address of your server? |g|e.g. zkillboard.com|n|");
$settings["baseaddr"] = CLI::prompt("Domain name?", "zkillboard.com");

// Log
$settings["logfile"] = CLI::prompt("Log file location?", "/var/log/zkb.log");

// Image server
CLI::out("Image and API server defaults to the zKillboard proxies, you can however use CCPs servers if you want: |g|https://api.eveonline.com and https://image.eveonline.com|n|");
$settings["apiserver"] = CLI::prompt("API Server?", "https://api.zkillboard.com/");
$settings["imageserver"] = CLI::prompt("Image Server?", "https://image.zkillboard.com/");

// Secret key for cookies
CLI::out("A secret key is needed for your cookies to be encrypted.");
$cookiesecret = CLI::prompt("Secret key for cookies?", uniqid(time()));
$settings["cookiesecret"] = sha1($cookiesecret);

// Get default config
$configFile = file_get_contents("$base/config.new.php");

// Create the new config
foreach($settings as $key=>$value) {
	$configFile = str_replace("%$key%", $value, $configFile);
}

// Save the file and then attempt to load and initialize from that file
$configLocation = "$base/../config.php";
if (file_put_contents($configLocation, $configFile) === false) CLI::out("|r|Unable to write configuration file at $configLocation", true);

try {
	CLI::out("|g|Config file written, now attempting to initialize settings");
	$one = Db::queryField("select 1 one from dual", "one", array(), 1);
	if ($one != "1")
		throw new Exception("We were able to connect but the database did not return the expected '1' for: select 1 one from dual;");
	CLI::out("|g|Success! Database initialized.");
} catch (Exception $ex) {
	CLI::out("|r|Error! Removing configuration file.");
	unlink($configLocation);
	throw $ex;
}

// Move bash_complete_zkillboard to the bash_complete folder
try {
	file_put_contents("/etc/bash_completion.d/zkillboard", file_get_contents("$base/bash_complete_zkillboard"));
	exec("chmod +x $base/../cli.php");
} catch (Exception $ex) {
	CLI::out("|r|Error! Couldn't move the bash_complete file into /etc/bash_completion.d/, please do this after the installer is done.");
}

// Now install the db structure
try {
	$sqlFiles = scandir("$base/sql");
	foreach($sqlFiles as $file) {
		if (Util::endsWith($file, ".sql.gz")) {
			$table = str_replace(".sql.gz", "", $file);
			CLI::out("Adding table |g|$table|n| ... ");
			$sqlFile = "$base/sql/$file";
			loadFile($sqlFile);
			CLI::out("|g|done");
		}
	}
} catch (Exception $ex) {
	CLI::out("|r|Error! Removing configuration file.");
	unlink($configLocation);
	throw $ex;
}

// Launch the EDK transfer crap
if(strtolower(CLI::prompt("|g|Do you want to migrate kills from an existing EDK installation? |r|(experimental)|n|", "y/N")) == "y")
{
	$edkPath = CLI::prompt("Root path of your edk installation?");
	if($edkPath)
	{
		$cmd = "";

		if(defined("PHP_BINARY"))
			$cmd = PHP_BINARY . " ";
		else
			$cmd = "php ";

		$cmd .= escapeshellarg('$base/edkConversion.php') . " ";
		$cmd .= escapeshellarg($edkPath) . " ";
		$cmd .= escapeshellarg($settings["dbhost"]) . " ";
		$cmd .= escapeshellarg($settings["dbuser"]) . " ";
		$cmd .= escapeshellarg($settings["dbpassword"]) . " ";
		$cmd .= escapeshellarg($settings["dbname"]);

		passthru($cmd);
	}
}

function loadFile($file) {
	if (Util::endsWith($file, ".gz")) $handle = gzopen($file, "r");
	else $handle = fopen($file, "r");

	$query = "";
	while ($buffer = fgets($handle)) {
		$query .= $buffer;
		if (strpos($query, ";") !== false) {
			$query = str_replace(";", "", $query);
			Db::execute($query);
			$query = "";
		}
	}
	fclose($handle);
}