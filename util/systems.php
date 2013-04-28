<?php
require_once "../init.php";

$arr = array();
$result = Db::query("SELECT typeID as shipTypeID, typeName as shipTypeName FROM ccp_invTypes WHERE groupID IN ((SELECT groupID FROM ccp_invGroups WHERE categoryID =6))", array());
foreach($result as $entry)
{
	$id = (int) $entry["shipTypeID"];
	$arr[$id] = $entry["shipTypeName"];
}
echo json_encode($arr);

/*$result = Db::query("SELECT security, securityClass, radius, solarSystemName, luminosity, y, x, z, solarSystemID FROM ccp_systems", array());
$arr = array();
foreach($result as $system)
{
	$z = abs($system["z"]);
	$arr[] = array("security_level" => (float) $system["security"], "security_class" => $system["securityClass"], "radius" => (float) $system["radius"], "name" => $system["solarSystemName"],
	"y" => (float) $system["y"], "x" => (float) $system["x"], "z" => $z, "id" => (int) $system["solarSystemID"]);
}

echo json_encode($arr);
*/
