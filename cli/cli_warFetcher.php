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
use Perry\Perry;
use Perry\Setup;
use Perry\Cache\File\FilePool;

class cli_warFetcher implements cliCommand
{
	public function getDescription()
	{
		return "Prepopulates the warID into the zz_wars table for processing.";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	/**
		Fetch new wars every 3 hours
	*/
        public function getCronInfo()
        {
                return array(10800 => "");
        }

	public function execute($parameters, $db)
	{
		Setup::getInstance()->cacheImplementation = new FilePool("/tmp/wars/");
		Setup::$cacheTTL = 60000;

		$next = "http://public-crest.eveonline.com/wars/";
		do {
			$perrywars = Perry::fromUrl($next);
			$next = @$perrywars->next->href;
			foreach($perrywars->items as $war)
			{
				$id = $war->id;
				if ($id > 0) Db::execute("insert ignore into zz_wars (warID) values (:warID)", array(":warID" => $id));
			}
			sleep(1);
		} while ($next != null);
	}
}
