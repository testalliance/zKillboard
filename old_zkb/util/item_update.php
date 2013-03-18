<?php

require_once "../init.php";

$rows = Db::query("select typeID from invTypes order by typeID");
$ids = array();
foreach($rows as $row) {
        $ids[] = $row['typeID'];
}

$size = sizeof($ids);
$count = 0;

$buckets = array();
$bucketNumber = 0;
$bucketSize = 50;
do {
    $currentBucket = array();
    $start = $bucketNumber * $bucketSize;
    $end = $start + $bucketSize;
    for ($i = $start; $i < $end && $i < $size; $i++) {
        if ($ids[$i] != "") $currentBucket[] = $ids[$i];
    }

    $buckets[$bucketNumber] = $currentBucket;
    $bucketNumber++;
} while ($bucketNumber * $bucketSize < sizeof($ids));

foreach ($buckets as $bucket) {
        $exploded = implode(",", $bucket);
        $url = trim("http://api.eve-kill.net/eve/typeName.xml.aspx?ids=$exploded");
        $raw = file_get_contents($url);
		try {
        	$xml = new SimpleXmlElement($raw);
		} catch (Exception $ex) {
			print_r($ex);
			echo "There was a problem retrieving the XML from $url\nThis could be because of the network, local server settings, CCP, etc.";
			die();
		}
        foreach ($xml->result->rowset->row as $row) {
			$count++;
            $id = $row["typeID"];
			$currentName = Db::queryField("select typeName from invTypes where typeID = :typeID", "typeName", array(":typeID" => $id), 0);
            $name = trim($row["typeName"]);
			if ($currentName === $name) continue;
			if (strlen($name) == 0) {
				//echo "$count/$size $id skipped, CCP returned a blank name and we will keep the old one. $currentName\n";
				continue;  // CCP removed an item and cleared the name, we'll keep the name around though
			}
            Db::execute("update invTypes set typeName = :name where typeID = :id", array(":name" => $name, ":id" => $id));
            echo "$count/$size $id $currentName -> $name\n";
        }
}
echo "Finished\n";
