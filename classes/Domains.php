<?php
class Domains
{
	public static function getEntities($domain)
	{
		$entities = Db::queryField("SELECT entities FROM zz_domains WHERE domainName = :domain", "entities", array(":domain" => $domain), 0);
		return json_decode($entities, true);
	}

	public static function setTracker($array)
	{
		foreach ($array as $ent)
		{
			$row = array($ent["type"] . "ID" => $ent["id"], "name" => $ent["name"]);
			Util::setSubdomainGlobals($ent["type"] . "ID", $row, $ent["type"]);
		}
	}

	public static function getUserEntities($userid)
	{
		$entities = Db::query("SELECT * FROM zz_domains WHERE userID = :userid", array(":userid" => $userid), 0);
		$return = array();
		foreach ($entities as $js)
		{
			$return[$js["domainName"]] = json_decode($js["entities"], true);
		}
		return $return;
	}

	public static function setEntities($array, $domain)
	{
		$userID = User::getUserID();
		$entities = json_encode(array_values($array));
		Db::execute("INSERT INTO zz_domains (userID, domainName, entities) VALUES (:userID, :domainName, :entities) ON DUPLICATE KEY UPDATE entities = :entities", array(":userID" => $userID, ":domainName" => $domain, ":entities" => $entities));
	}

	public static function updateEntities($domain, $name, $type)
	{
		if ($type == "character")
			$query = "SELECT characterID AS id, name FROM zz_characters WHERE name = :name";
		if ($type == "corporation")
			$query = "SELECT corporationID AS id, name FROM zz_corporations WHERE name = :name AND memberCount > 0";
		if ($type == "alliance")
			$query = "SELECT allianceID AS id, name FROM zz_alliances WHERE name = :name AND memberCount > 0";
		if ($type == "faction")
			$query = "SELECT factionID AS id, name FROM zz_factions WHERE name = :name";
		if ($type == "ship")
			$query = "SELECT typeID as id, typeName AS name FROM ccp_invTypes WHERE typeName = :name";
		if ($type == "system")
			$query = "SELECT solarSystemID AS id, solarSystemName AS name FROM ccp_systems WHERE solarSystemName = :name";
		if ($type == "region")
			$query = "SELECT regionID AS id, regionName AS name FROM ccp_regions WHERE regionName = :name";

		$id = Db::queryField($query, "id", array(":name" => $name));
		$userID = User::getUserID();
		$entities = self::getEntities($domain);
		if ($entities == NULL)
			$entities = array();
		$ent = array("id" => $id, "type" => $type, "name" => $name);
		if (!in_array($ent, $entities)) {
			$entities[] = $ent;
			$entities = json_encode($entities);
			Db::execute("INSERT INTO zz_domains (userID, domainName, entities) VALUES (:userID, :domainName, :entities) ON DUPLICATE KEY UPDATE entities = :entities", array(":userID" => $userID, ":domainName" => $domain, ":entities" => $entities));
		}
		else
			return "$name is already added to $domain";
	}

	public static function deleteEntity($domain, $entity)
	{
		$entities = self::getEntities($domain);
		foreach ($entities as $key => $val)
		{
			if ($val["id"] == $entity)
				unset($entities[$key]);
		}
		self::setEntities($entities, $domain);
	}
}