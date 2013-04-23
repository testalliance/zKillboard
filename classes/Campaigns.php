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

/**
 * Helper functions for Campaign handling
 */

class Campaigns
{
	/**
	 * Gets all campaigns available
	 * @return array
	 */
	public static function getAllCampaigns()
	{
		return Db::query("SELECT c.*, u.username FROM zz_campaigns c JOIN zz_users u ON u.id = c.userID WHERE c.campaignActive is true");
	}

	/**
	 * Gets a users campaigns (Created and followed)
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @return array
	 */
	public static function getMyCampaigns($userID)
	{
	}

	/**
	 * Follows a campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The ID of the campaign
	 * @return array
	 */
	public static function followCampaign($userID, $campaignID)
	{
	}

	/**
	 * Unfollows a campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The id of the campaign
	 * @return array
	 */
	public static function unFollowCampaign($userID, $campaignID)
	{
	}

	/**
	 * Creates a campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @return array
	 */
	public static function createCampaign($userID)
	{
	}

	/**
	 * Deletes a campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The ID of the campaign
	 * @return array
	 */
	public static function deleteCampaign($userID, $campaignID)
	{
	}

	/**
	 * End a campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The ID of the campaign
	 * @return array
	 */
	public static function endCampaign($userID, $campaignID)
	{
	}

	/**
	 * Adds an entity to the campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The ID of the campaign
	 * @param $entityID The ID of the entity
	 * @return array
	 */
	public static function addEntityToCampaign($userID, $campaignID, $entityID)
	{
	}

	/**
	 * Removes an entity from the campaign
	 * 
	 * @static
	 * @param $userID The ID of the user.
	 * @param $campaignID The ID of the campaign
	 * @param $entityID The ID of the entity
	 * @return array
	 */
	public static function removeEntityFromCampaign($userID, $campaignID, $entityID)
	{
	}
}