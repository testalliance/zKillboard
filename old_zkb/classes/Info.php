<?php

class Info
{

    /**
     * Retrieve the system id of a solar system.
     *
     * @static
     * @param  $systemName
     * @return int The solarSystemID
     */
    public static function getSystemID($systemName)
    {
        return Db::queryField("select solarSystemID from mapSolarSystems where solarSystemName = :name", "solarSystemID",
                              array(":name" => $systemName), 86400);
    }

    /**
     * @static
     * @param  $systemID
     * @return array Returns an array containing the solarSystemName and security of a solarSystemID
     */
    public static function getSystemInfo($systemID)
    {
        return Db::queryRow("select solarSystemName, security from mapSolarSystems where solarSystemID = :systemID", array(":systemID" => $systemID), 86400);
    }

    /**
     * @static
     * @param  $systemID
     * @return string The system name of a solarSystemID
     */
    public static function getSystemName($systemID)
    {
        $systemInfo = Info::getSystemInfo($systemID);
        return $systemInfo['solarSystemName'];
    }

    /**
     * @static
     * @param  int $systemID
     * @return double The system secruity of a solarSystemID
     */
    public static function getSystemSecurity($systemID)
    {
        $systemInfo = Info::getSystemInfo($systemID);
        return $systemInfo['security'];
    }

    /**
     * @static
     * @param  $typeID
     * @return string The item name.
     */
    public static function getItemName($typeID)
    {
        $name = Db::queryField("select typeName from invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID), 3600);
		if ($name === null) {
			Db::execute("insert ignore into invTypes (typeID, typeName) values (:typeID, :typeName)", array(":typeID" => $typeID, ":typeName" => "TypeID $typeID"));
			$name = "TypeID $typeID";
		}
		return $name;
    }

    /**
     * @param  $itemName
     * @return int The typeID of an item.
     */
    public static function getItemID($itemName)
    {
        return Db::queryField("select typeID from invTypes where upper(typeName) = :typeName", "typeID", array(":typeName" => strtoupper($itemName)), 86400);
    }

    /**
     * Retrieves the effectID of an item.  This is useful for determining if an item is fitted into a low,
     * medium, high, rig, or t3 slot.
     *
     * @param  $typeID
     * @return int The effectID of an item.
     */
    public static function getEffectID($typeID)
    {
        return Db::queryField("select effectID from dgmTypeEffects where typeID = :typeID and effectID in (11, 12, 13, 2663, 3772)", "effectID", array(":typeID" => $typeID), 86400);
    }

	
	public static function getCorpId($name) {
		global $dbPrefix;

		return Db::queryField("select corporation_id from {$dbPrefix}corporations where name = :name", "corporation_id", array(":name" => $name));
	}

	public static function getAlliName($id) {
		global $dbPrefix;
	
		return Db::queryField("select name from {$dbPrefix}alliances where alliance_id = :id", "name", array(":id" => $id));
	}

    /**
     * Attempt to find the name of a corporation in the corporations table.  If not found the
     * and $fetchIfNotFound is true, it will then attempt to pull the name via an API lookup.
     *
     * @static
     * @param  $id
     * @param bool $fetchIfNotFound
     * @return string The name of the corp if found, null otherwise.
     */
    public static function getCorpName($id, $fetchIfNotFound = false)
    {
		global $dbPrefix;
        $name = Db::queryField("select name from {$dbPrefix}corporations where corporation_id = :id", "name", array(":id" => $id), $fetchIfNotFound
                                                                                         ? 0 : 86400);
        if ($name != null || $fetchIfNotFound == false) return $name;

        $pheal = new Pheal();
        $pheal->scope = "corp";
        $corpInfo = $pheal->CorporationSheet(array("corporationID" => $id));
        $name = $corpInfo->corporationName;
        if ($name != null) {// addName($id, $name, 1, 2, 2);
			Db::execute("insert into {$dbPrefix}corporations (corporation_id, name) values (:id, :name)", array(":id" => $id, ":name" => $name));
		}
        return $name;
    }

	public static function getAlliId($name) {
		global $dbPrefix;

		return Db::queryField("select alliance_id from {$dbPrefix}alliances where name = :name", "alliance_id", array(":name" => $name));
	}

	public static function getCharId($name) {
		global $dbPrefix;

		return Db::queryField("select character_id from {$dbPrefix}characters where name = :name", "character_id", array(":name" => $name));
	}

    /**
     * Attempt to find the name of a character in the characters table.  If not found the
     * and $fetchIfNotFound is true, it will then attempt to pull the name via an API lookup.
     *
     * @static
     * @param  $id
     * @param bool $fetchIfNotFound
     * @return string The name of the corp if found, null otherwise.
     */
    public static function getCharName($id, $fetchIfNotFound = false)
    {
		global $dbPrefix;
        $name = Db::queryField("select name from {$dbPrefix}characters where character_id = :id", "name", array(":id" => $id), $fetchIfNotFound
                                                                                         ? 0 : 86400);
        if ($name != null || $fetchIfNotFound == false) return $name;

        $pheal = new Pheal();
        $pheal->scope = "eve";
        $charInfo = $pheal->CharacterInfo(array("characterid" => $id));
        $name = $charInfo->characterName;
        if ($name != null) { //addName($id, $name, 1, 1, null);
			Db::execute("insert into {$dbPrefix}characters (character_id, name) values (:id, :name)", array(":id" => $id, ":name" => $name));
		}
        return $name;
    }

    /**
     * Get the name of the group
     *
     * @static
     * @param int $groupID
     * @return string
     */
    public static function getGroupName($groupID)
    {
        $name = Db::queryField("select groupName from invGroups where groupID = :id", "groupName", array(":id" => $groupID), 86400);
        return $name;
    }
}
