<?php

require_once "../init.php";

$prices = file_get_contents("http://eve.no-ip.de/prices/30d/prices-all.xml");
#$prices = file_get_contents("prices-all.xml");

$xml = new SimpleXmlElement($prices);

//print_r($xml->result->rowset->row); die();

$motherships = Db::query("select typeid from invTypes where groupid = 659");
foreach ($motherships as $mothership) {
	$typeID = $mothership['typeid'];
	Db::execute("replace into zz_prices (typeID, price) values (:typeID, :price)", array(":typeID" => $typeID, ":price" => 15000000000));
}

$titans = Db::query("select typeid from invTypes where groupid = 30");
foreach ($titans as $titan) {
	$typeID = $titan['typeid'];
	Db::execute("replace into zz_prices (typeID, price) values (:typeID, :price)", array(":typeID" => $typeID, ":price" => 65000000000));
}

foreach($xml->result->rowset->row as $row) {
	$typeID = $row['typeID'];
	$avgPrice = $row['avg'];
	Db::execute("delete from zz_prices where typeID = :typeID", array(":typeID" => $typeID));
	Db::execute("insert into zz_prices (typeID, price) values (:typeID, :price)", array(":typeID" => $typeID, ":price" => $avgPrice));
}
