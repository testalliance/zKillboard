<?php


if(php_sapi_name() != "cli")
    die("This is a cli script!");

if(!extension_loaded('pcntl'))
    die("This script needs the pcntl extension!");

$base = __DIR__;
require_once( "$base/../config.php" );

if($debug)
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// vendor autoload
require( "$base/../vendor/autoload.php" );

// zkb class autoloader
spl_autoload_register("zkbautoload");

function zkbautoload($class_name)
{
    $baseDir = dirname(__FILE__);
    $fileName = "$baseDir/../classes/$class_name.php";
    if (file_exists($fileName))
    {  
        require_once $fileName;
        return;
    }
}

$file = $argv[1];
if (strlen($file) == 0) die("Unable to determine file name\n");
$fieldLocations = array();
$typeRegionClear = array();
$storage = array();
$count1 = 0;

array_shift($argv);

echo "Clearing expired data...";
$count = Db::execute("delete from zz_marketdata where insert_dttm < date_sub(now(), interval 24 hour)");
echo " Done ($count)\n";
echo "Updating PID's ... ";
$count = Db::execute("update zz_marketdata set pid = 0 where pid != 0");
echo " Done ($count)\n";

$marketHubs = array(60003760,60008494,60004588,60011866,60005686,60003787,60001096,60011740);

foreach($argv as $file) {
	if ($file == "*.csv") exit;
	$contents = file_get_contents($file);

	echo "Loading $file\n";

	$json = json_decode($contents, true);
	$fields = $json["columns"];

	if (in_array("stationID", $fields)) {

		$fieldLocations = array();
		$index = 0;
		foreach ($fields as $field) {
			$fieldLocations[strtolower($field)] = $index;
			$index++;
		}

		foreach($json["rowsets"] as $set) {
			$typeID = $set["typeID"];
			$regionID = $set["regionID"];

			foreach($set["rows"] as $row) {
				$stationID = $row[$fieldLocations["stationid"]];
				$bid = $row[$fieldLocations["bid"]] == 1 ? "Y" : "N";

				if (!in_array($stationID, $marketHubs)) continue;
				$key = "$typeID|$regionID|$stationID|$bid";

				$clearIt = !isset($typeRegionClear["$key"]);
				$typeRegionClear["$key"] = true;

				if ($clearIt || !isset($storage[$key])) {
					unset($storage[$key]);
					$storage[$key] = array();
				}
				if (sizeof($storage[$key]) == 0) $storage[$key][0] = $row;
				else {
					$prevFields = $storage[$key][0];
					$prevPrice = $prevFields[$fieldLocations["price"]];
					$price = $row[$fieldLocations["price"]];
					if ($bid == "N") $row = $prevPrice < $price ? $prevFields : $row;
					else $row = $prevPrice > $price ? $prevFields : $row;
					$storage[$key][0] = $row;
				}
				$count1++;
			}
		}
	}
	unlink($file);
	echo "Done: $file\n";
}

$total = sizeof($storage);

echo "Records: $total\n";
$inserts = 0;
$toBeInserted = 0;
$whereStatements = array();

foreach($storage as $key=>$fields) {
	$ids = explode("|", $key);
	$typeID = $ids[0];
	$regionID = $ids[1];
	$inserts++;

	$whereStatements[] = " (typeID = $typeID and regionID = $regionID) ";


	if ($inserts % 100 == 0 || $inserts >= $total) {
		$whereStatement = implode(" or ", $whereStatements);
		Db::execute("update zz_marketdata set pid = 1 where $whereStatement");
		$whereStatements = array();
	}
	echo ".";
	$toBeInserted += sizeof($fields);
	if ($inserts % 100 == 0) echo "\nDeleted: $inserts / $total ($toBeInserted)\n";
}
echo "\n";

$inserts = 0;
$total = $toBeInserted;
$values = array();

Db::execute("create temporary table if not exists tempData select * from zz_marketdata where 1 = 0");
Db::execute("truncate tempData");

foreach($storage as $key=>$fields) {
	$ids = explode("|", $key);
	$typeID = $ids[0];
	$regionID = $ids[1];
	foreach($fields as $field) {
		$price = $field[$fieldLocations["price"]];
		$bid = $field[$fieldLocations["bid"]];
		if (strtolower($bid) == "true" || $bid == "1") $bid = 1;
		else if ($bid == "" || $bid == "0" || strtolower($bid) == "false") $bid = 0;
		$minVolume = $field[$fieldLocations["minvolume"]];
		$volRemaining = $field[$fieldLocations["volremaining"]];
		$volEntered = $field[$fieldLocations["volentered"]];
		$range = $field[$fieldLocations["range"]];
		$orderID = $field[$fieldLocations["orderid"]];
		$issued = $field[$fieldLocations["issuedate"]];
		$issued = strtotime($issued);
		$duration = $field[$fieldLocations["duration"]];
		$stationID = $field[$fieldLocations["stationid"]];
		$solarSystemID = $field[$fieldLocations["solarsystemid"]];
		$expires = $issued + ($duration * 86400);
		$inserts++;

		$values[] = "($typeID, $bid, $price, $minVolume, $volRemaining, $volEntered, $range, $orderID, '$issued', $duration, '$expires', $stationID, $solarSystemID, $regionID, 0)";

		if ($inserts % 25 == 0 || $inserts >= $total) {
			$allValues = implode(", ", $values);
			$values = array();

			Db::execute("insert into tempData (typeID, bid, price, minVolume, volRemaining, volEntered, bid_range, orderID, issued, duration, expires, stationID, solarSystemID, regionID, pid) values $allValues");
		}

		echo ".";
		if ($inserts % 100 == 0) echo "\nInserted: $inserts / $total\n";
	}
}
echo "\nInserting data... ";
Db::execute("update tempData set insert_dttm = now()");
	$count = Db::execute("insert into zz_marketdata select * from (select * from tempData) as foo on duplicate key update 
			volRemaining = foo.volRemaining, issued = foo.issued, expires = foo.expires, price = foo.price, pid = 0, insert_dttm = now()

			");
	echo " Done ($count)\n";
	echo "Clearing completed/expired market orders... ";
	$count = Db::execute("delete from zz_marketdata where pid = 1");
	echo "Done ($count)\n";

	$typeIDs = Db::query("select distinct typeid from zz_marketdata where profitChecked = 0", array(), 0);
	Db::execute("update zz_marketdata set profitChecked = 1 where profitChecked = 0");

	$count = 0;
	$totalSize = sizeof($typeIDs);

die();


	foreach ($typeIDs as $row) {
		$count++;
		echo "$count / $totalSize \n";
		$typeID = $row["typeid"];
		Db::execute("delete from zz_profits where typeid = :typeID", array(":typeID" => $typeID));
		$volume = Db::queryField("select count(*) volume from zz_marketdata where typeid = :typeID", "volume", array(":typeID" => $typeID), array(), 0);
		Db::execute("
				insert into zz_profits select sell.typeid, sell.orderID, bid.orderID, least(bid.volRemaining, sell.volRemaining) volume, (least(bid.volRemaining, sell.volRemaining) * (bid.price - sell.price)) profit, now()  from zz_marketdata sell left join zz_marketdata bid on (sell.typeID = bid.typeID) left join mapSolarSystems sellSystem on (sell.solarSystemID = sellSystem.solarSystemID) left join mapSolarSystems bidSystem on (bid.solarSystemID = bidSystem.solarSystemID) where round(sellSystem.security, 1) >= 0.1 and round(bidSystem.security, 1) >= 0.1 and bid.bid = 1 and sell.bid = 0 and bid.minVolume = 1 and sell.typeid = :typeID and (bid.price - sell.price) > (0.03 * sell.price) group by sell.typeid, sell.orderID, bid.orderID  order by profit desc limit 50
				", array(":typeID" => $typeID));
		Db::execute("delete from zz_profits where profit < 1000000 and typeID = :typeID", array(":typeID" => $typeID));
	}

// Clear old orders
Db::execute("delete from zz_profits where insert_dttm < date_sub(now(), interval 48 hour)");
Db::execute("delete from zz_profits where typeid not in (select distinct typeid from zz_marketdata)");
