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
	public static function getUserTrackerDomains($userID)
	{
		$domains = Db::query("SELECT * FROM zz_domains WHERE userID = :userID", array(":userID" => $userID), 0);
		return $domains;
	}
	public static function addUserTrackerDomain($userID, $domainName)
	{
		$expire = date("Y-m-d H:i:s", time()+7776000);
		Db::execute("INSERT INTO zz_domains (userID, expirationDTTM, domain) VALUES (:userID, :expirationDTTM, :domain)", array(":userID" => $userID, ":expirationDTTM" => $expire, ":domain" => $domainName));
	}
	public static function deleteUserTrackerDomain($userID, $domainID)
	{
		Db::execute("DELETE FROM zz_domains WHERE domainID = :domainID and userID = :userID", array(":domainID" => $domainID, ":userID" => $userID));
		Db::execute("DELETE FROM zz_domains_entities WHERE domainID = :domainID", array(":domainID" => $domainID));
	}
	public static function getUserTrackerEntities($domainID)
	{
		$entities = Db::query("SELECT * FROM zz_domains_entities WHERE domainID = :domainID", array(":domainID" => $domainID), 0);
		return $entities;
	}

	public static function deleteUserTrackerEntity($domainID, $entityID, $entityType)
	{
		Db::execute("DELETE FROM zz_domains_entities WHERE domainID = :domainID AND entityID = :entityID AND entityType = :entityType", array(":domainID" => $domainID, ":entityID" => $entityID, ":entityType" => $entityType));
	}

	public static function addUserTrackerEntity($domainID, $entityID, $entityType, $entityName)
	{
		Db::execute("INSERT INTO zz_domains_entities (domainID, entityID, entityType, entityName) VALUES (:domainID, :entityID, :entityType, :entityName)", array(":domainID" => $domainID, ":entityID" => $entityID, ":entityType" => $entityType, ":entityName" => $entityName));
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
					Log::ircAdmin("|r|Problem deleting |g|http://$domain.zkillboard.com|r| from CloudFlare");
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
}
