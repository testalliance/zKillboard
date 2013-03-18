<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = dirname(__FILE__);
require_once "$base/../init.php";

require_once "apipull.php";
require_once "alliance.php";

array_shift($argv);

if (sizeof($argv) == 0) die("No files to process... bailing\n");

$job = "processRawApi";

try {
    echo 'f';
		foreach($argv as $file) {
				$filename = "/var/log/api_killlogs/" . $file;
				//echo "$filename\n";
				try {
						$raw = file_get_contents($filename);
						$xml = new SimpleXmlElement($raw);
						$pheal = new PhealResult($xml);
						$job($pheal);
				} catch (Exception $ex) {
						// ignore it!
				}
				unlink($filename);
		}
} catch (Exception $ex) {
		print_r($ex);
		// blah
}
Db::execute("delete from cronlock where a = '$job'");


function getLoad() {
		$output = array();
		$result = exec("cat /proc/loadavg", $output);

		$split = explode(" ", $result);
		$load = $split[0];
		return $load;
}
