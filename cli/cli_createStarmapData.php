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

class cli_createStarmapData implements cliCommand
{
	public function getDescription()
	{
		return "Creates the assests needed for the starmap. Types available: |g|ships, systems, all|n|. Usage: |g|createStarmapData <type>";
	}

	public function getAvailMethods()
	{
		return "ships systems all"; // Space seperated list
	}
	
	public function execute($parameters)
	{
		switch(@$parameters[0])
		{
			case "ships":
				ships();
			break;

			case "systems":
				systems();
			break;

			case "all":
				ships();
				systems();
			break;

			default:
				CLI::out("Please use |g|ships, systems or all|n| with this command", true);
			break;
		}		
	}
}

function systems()
{
	global $base;
	$bold = array("Freighter", "Carrier", "Dreadnought", "Capital Industrial Ship", "Jump Freighter", "Supercarrier", "Titan");
	CLI::out("Loading systems");
	$systems_result = Db::query("SELECT solarSystemID, solarSystemName, security, x, y, z FROM ccp_systems");
	$systems = array();
	foreach($systems_result as $system)
	{
		$systems[$system['solarSystemID']] = array(
			'name' => $system['solarSystemName'],
			'sec' => (($system['security'] < 0) ? 0 : (round($system['security'] , 1) * 10)),
			'x' => $system['x'],
			'y' => $system['y'],
			'z' => ($system['z'] * -1)
		);
	}
	CLI::out("Creating system JSON data");
	$json = json_encode($systems, JSON_NUMERIC_CHECK);
	CLI::out("Unlinking old file");
	@unlink("$base/public/js/starmap-systems.json");
	CLI::out("Creating new system JSON file");
	file_put_contents("$base/public/js/starmap-systems.json", $json);
	CLI::out("Done");
}

function ships()
{
	global $base;
	$bold = array("Freighter", "Carrier", "Dreadnought", "Capital Industrial Ship", "Jump Freighter", "Supercarrier", "Titan");
	CLI::out("Loading ships");
	$ships_results = Db::query("SELECT ccp_invTypes.typeID, typeName, groupName FROM ccp_invTypes INNER JOIN ccp_invGroups ON ccp_invGroups.groupID = ccp_invTypes.groupID WHERE (categoryID IN (6, 23, 40) AND ccp_invTypes.published = 1 AND ccp_invGroups.published = 1 OR typeID = 670)");
	$ships = array();
	foreach($ships_results as $ship)
	{
		$ships[$ship['typeID']] = array('name' => $ship['typeName'], 'bold' => ((in_array($ship['groupName'], $bold)) ? 1 : 0));
	}
	CLI::out("Creating ship JSON data");
	$json = json_encode($ships, JSON_NUMERIC_CHECK);
	CLI::out("Unlinking old file");
	@unlink("$base/public/js/starmap-ships.json");
	CLI::out("Creating new ship JSON file");
	file_put_contents("$base/public/js/starmap-ships.json", $json);
	CLI::out("Done");
}