<?php
/* TEST zKB Auth API management
 * Copyright (C) 2013 Test Alliance Please Ignore (TEST).
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class cli_authAPI implements cliCommand
{
	public function getDescription()
	{
		return "Imports EVE Online API credentials from TEST's Auth system.";
	}

	public function getAvailMethods()
	{
		return "import";
	}

	public function getCronInfo()
	{
                return array(
                        3600 => "import"
                );
	}

	public function execute($parameters, $db)
	{
		# select corporationID, name from zz_corporations where allianceID=498125261 order by name; [{"id":"1018389948","name":"Dreddit"},{"id":"98171228","name":"SergalJerk"}]
                require('/home/dreddit/www/zkb.pleaseignore.com/config.php');
                $skipUIDS = array();
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|help <command>|n| To see a list of commands, use: |g|list", true);
		$command = $parameters[0];

		switch($command)
		{
			case "import":
				CLI::out("Importing API credentials from Auth...");
                                
                                foreach ($authAlliances as $alliance)
                                {
                                    CLI::out("Importing API credentials for $alliance");
                                    $url = $authURL."".$alliance;
                                    $requestoutput = file_get_contents($url);
                                    foreach (json_decode($requestoutput, true) as $apikey)
                                    {
                                            if (in_array($apikey['api_user_id'], $skipUIDS)) {
                                                    continue;
                                            }
                                            CLI::out("Importing " . $apikey['characters__corporation__name'] . " - " . $apikey['characters__name']);
                                            Db::execute("insert into zz_api (userID, keyID, vCode, label) values (1, '".$apikey['api_user_id']."', '".$apikey['api_key']."', \"".$apikey['characters__corporation__name']." - ".$apikey['characters__name']."\") ON DUPLICATE KEY UPDATE keyID='".$apikey['api_user_id']."', vCode='".$apikey['api_key']."', label=\"".$apikey['characters__corporation__name']." - ".$apikey['characters__name']."\"");
}
                                }
				CLI::out("done.");
			break;
		}
	}
}
