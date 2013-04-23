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
 * Starmap Assest Generation
 * 
 * Usage:
 * <code> $ php5 /path/to/zKillboard/util/createStarmap.php </code>
 */

// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

//get the init stuff
require_once dirname(__FILE__) . '/../init.php';

//create a list of shiptypes to make bold
$bold = array('Freighter', 'Carrier', 'Dreadnought', 'Capital Industrial Ship', 'Jump Freighter', 'Supercarrier', 'Titan');

//run the stuff
switch (@$argv[1])
{
	case 'doShips':
		$ships_result = Db::query('SELECT ccp_invTypes.typeID, typeName, groupName FROM ccp_invTypes INNER JOIN ccp_invGroups ON ccp_invGroups.groupID = ccp_invTypes.groupID WHERE (categoryID IN (6, 23, 40) AND ccp_invTypes.published = 1 AND ccp_invGroups.published = 1 OR typeID = 670)');
		foreach ($ships_result as $row)	{ $ships[$row['typeID']] = array('name' => $row['typeName'], 'bold' => ((in_array($row['groupName'], $bold)) ? 1 : 0)); }
		echo json_encode($ships, JSON_NUMERIC_CHECK);
	break;

	case 'doSystems':
		$systems_result = Db::query('SELECT solarSystemID, solarSystemName, security, x, y, z FROM ccp_systems');
		//$systems_result = Db::query('SELECT solarSystemID, solarSystemName, (CASE WHEN security < 0 THEN 0 ELSE ROUND(security, 1) * 10 END) AS security, x, y, z FROM ccp_systems WHERE x < 0');
		foreach ($systems_result as $row) { $systems[$row['solarSystemID']] = array('name' => $row['solarSystemName'], 'sec' => (($row['security'] < 0) ? 0 : (round($row['security'] , 1) * 10)), 'x' => $row['x'], 'y' => $row['y'], 'z' => ($row['z'] * -1)); }
		echo json_encode($systems, JSON_NUMERIC_CHECK);
	break;

	default:
		echo "Creating JSON Files...";
		exec('php5 ' . dirname(__FILE__) . '/createStarmap.php doShips > ' . str_replace('util', 'public/js/', dirname(__FILE__)) . 'starmap-ships.json');
		exec('php5 ' . dirname(__FILE__) . '/createStarmap.php doSystems > ' . str_replace('util', 'public/js/', dirname(__FILE__)) . 'starmap-systems.json');
		echo "Done\n";
	break;
}