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

class Domains
{
	public static function getEntities($domain)
	{
		$entities = Db::queryField("SELECT entities FROM zz_domains WHERE domain = :domain", "entities", array(":domain" => $domain), 0);
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
		Db::execute("update zz_domains set expirationDTTM = date_add(createdDTTM, interval 90 day) where expirationDTTM is null");
		$entities = Db::query("SELECT * FROM zz_domains WHERE userID = :userid", array(":userid" => $userid), 0);
		$return = array();
		foreach ($entities as $js)
		{
			$return[$js["domain"]] = json_decode($js["entities"], true);
		}
		return $return;
	}

	public static function setEntities($array, $domain)
	{
		$userID = User::getUserID();
		$entities = json_encode(array_values($array));
		Db::execute("INSERT INTO zz_domains (userID, domain, entities) VALUES (:userID, :domain, :entities) ON DUPLICATE KEY UPDATE entities = :entities", array(":userID" => $userID, ":domain" => $domain, ":entities" => $entities));
	}

	public static function updateEntities($domain, $name, $type)
	{
		$userID = User::getUserID();
		$subDomain = strtolower($domain);
		// Make sure the domain isn't on our restricted list
		$restrictedSubDomains = array("www", "email", "mx", "ipv6", "blog", "forum", "cdn", "content", "static", "api", "image", "news");
		if (in_array($subDomain, $restrictedSubDomains)) return "$subDomain is a restricted subDomain";

		// Validate the domain, must start and end with a character and contain a-z, 0-9, or - only
		if (preg_match('/^[a-z][\-a-z0-9]+[a-z]$/', $subDomain) == 0) return "Invalid subDomain: $subDomain";

		// Make sure this toon owns this subdomain
		$count = Db::queryField("select count(*) count from zz_domains where domain = :subDomain and userID != :userID", "count", array(":subDomain" => $subDomain, ":userID" => $userID));
		if ($count > 0) return "Someone else has already claimed this domain...";

		if ($type == "character") $query = "SELECT characterID AS id, name FROM zz_characters WHERE name = :name";
		if ($type == "corporation") $query = "SELECT corporationID AS id, name FROM zz_corporations WHERE name = :name AND memberCount > 0";
		if ($type == "alliance") $query = "SELECT allianceID AS id, name FROM zz_alliances WHERE name = :name AND memberCount > 0";
		if ($type == "faction") $query = "SELECT factionID AS id, name FROM zz_factions WHERE name = :name";
		if ($type == "ship") $query = "SELECT typeID as id, typeName AS name FROM ccp_invTypes WHERE typeName = :name";
		if ($type == "system") $query = "SELECT solarSystemID AS id, solarSystemName AS name FROM ccp_systems WHERE solarSystemName = :name";
		if ($type == "region") $query = "SELECT regionID AS id, regionName AS name FROM ccp_regions WHERE regionName = :name";

		$id = Db::queryField($query, "id", array(":name" => $name));
		$entities = self::getEntities($domain);
		if ($entities == NULL) $entities = array();
		$ent = array("id" => $id, "type" => $type, "name" => $name);
		if (!in_array($ent, $entities)) {
			$entities[] = $ent;
			$entities = json_encode($entities);
			Db::execute("INSERT INTO zz_domains (userID, domain, entities) VALUES (:userID, :domain, :entities) ON DUPLICATE KEY UPDATE entities = :entities", array(":userID" => $userID, ":domain" => strtolower($domain), ":entities" => $entities));
		}
		return "$name is already added to $domain";
	}

	public static function deleteDomainsFromCloudflare() {
		$cf = null;
		$setToDelete = Db::query("SELECT * FROM zz_domains WHERE setToDelete is true", array(), 0);
		foreach($setToDelete as $row) {
			if($cf == null) {
				global $cfUser, $cfKey;
				$cf = new CloudFlare($cfUser, $cfKey);
			}

			$cfID = $row["cloudFlareID"];
			$domain = $row["domain"];
			$domainID = $row["domainID"];
			try {
				$return = $cf->delete_dns_record("zkillboard.com", $cfID);
				$result = $return["result"];
				if($result == "success"){
					Log::ircAdmin("Deleted |g| http://$domain.zkillboard.com|n| from CloudFlare");
					Db::execute("DELETE FROM zz_domains WHERE domainID = :domainID", array(":domainID" => $domainID));
				}
				else {
					Log::ircAdmin("|r|Problem deleting |g| http://$domain.zkillboard.com|r| from CloudFlare");
				}
			}
			catch (Exception $ex) {
				Log::ircAdmin("|r|Problem deleteting |g|http://$domain.zkillboard.com|r| from CloudFlare: |n|" . $ex->getMessage());
			}
		}
	}

	public static function registerDomainsWithCloudflare() {
		$cf = null;
		$needsRegistered = Db::query("select * from zz_domains where cloudFlareID is null", array(), 0);
		foreach($needsRegistered as $row) {
			if ($cf == null) {
				global $cfUser, $cfKey;
				$cf = new CloudFlare($cfUser, $cfKey);
			}

			$domainID = $row["domainID"];
			$subDomain = $row["domain"];
			try {
				// Flag it so repeats don't happen
				Db::execute("update zz_domains set cloudFlareID = -1 where domainID = :dID", array(":dID" => $domainID));
				$response = $cf->add_dns_record("zkillboard.com", "CNAME", "zkillboard.com", $subDomain, true);
				$cfID = $response["response"]["rec"]["obj"]["rec_id"];
				$cf->edit_dns_record("zkillboard.com", "CNAME", "zkillboard.com", $subDomain, $cfID, true);
				if ($cfID != null) {
					Db::execute("update zz_domains set cloudFlareID = :cfID where domainID = :dID", array(":dID" => $domainID, ":cfID" => $cfID));
					Log::ircAdmin("Registered |g|http://$subDomain.zkillboard.com|n| with CloudFlare");
				} else {
					Log::ircAdmin("|r|Problem registering |g|http://$subDomain.zkillboard.com|r| with CloudFlare: null cloudFlareID received");
				}
			} catch (Exception $ex) {
				Log::ircAdmin("|r|Problem registering |g|http://$subDomain.zkillboard.com|r| with CloudFlare: |n|" . $ex->getMessage());
			}
		}
	}

	public static function deleteEntity($domain, $entity)
	{
		$entities = self::getEntities($domain);
		foreach ($entities as $key => $val)
		{
			if ($val["id"] == $entity) unset($entities[$key]);
		}
		self::setEntities($entities, $domain);
	}
}
