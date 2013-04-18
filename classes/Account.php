<?php

class Account
{
	public static function getUserTrackerData()
	{
		$entities = array("character", "corporation", "alliance", "faction", "ship", "system", "region");
		$entitylist = array();
		
		foreach($entities as $ent)
		{
			$result = UserConfig::get($ent);
			$part = array();
			
			if($result != null) foreach($result as $row) {
				switch($ent)
				{
					case "system":
						$row["solarSystemID"] = $row["id"];
						$row["solarSystemName"] = $row["name"];
						$sunType = Db::queryField("SELECT sunTypeID FROM ccp_systems WHERE solarSystemID = :id", "sunTypeID", array(":id" => $row["id"]));
						$row["sunTypeID"] = $sunType;
					break;
					
					case "ship":
						$row["shipTypeID"] = $row["id"];
						$row["${ent}Name"] = $row["name"];
					break;
					
					default:
						$row["${ent}ID"] = $row["id"];
						$row["${ent}Name"] = $row["name"];
					break;
				}
				$part[] = $row;
			}
			$entlist[$ent] = $part;
		}
		return $entlist;
	}
}
