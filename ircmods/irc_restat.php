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

class irc_restat implements ircCommand {
        public function getRequiredAccessLevel() {
                return 10;
        }

        public function getDescription() {
                return "Allows you to recalculate stats for individual entities.  Valid entities: alli, corp, pilot, ship.  Example: |g|.z restat ship 33397";
        }

        public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
				if (sizeof($parameters) != 2) irc_error("Please specify entity and id");
                $validEntities = array("alli" => "allianceID", "corp" => "corporationID", "pilot" => "characterID", "ship" => "shipTypeID");
                $entity = $parameters[0];
                $id = (int) $parameters[1];
				if ($id == 0) irc_error("|r|Invalid entity id specified");
				if (!isset($validEntities[$entity])) irc_error("|r|Invalid entity type specified!");
				$column = $validEntities[$entity];

                self::recalc($entity, $column, $id);
                irc_out("|g|Stat recalculation for $entity $id has completed.");
        }

        public function isHidden() { return false; }

        private static function recalc($type, $column, $id, $calcKills = true)
        {
                Db::execute("drop table if exists zz_stats_temporary");
                Db::execute("
                                CREATE TEMPORARY TABLE `zz_stats_temporary` (
                                        `killID` int(16) NOT NULL,
                                        `groupName` varchar(16) NOT NULL,
                                        `groupNum` int(16) NOT NULL,
                                        `groupID` int(16) NOT NULL,
                                        `points` int(16) NOT NULL,
                                        `price` decimal(16,2) NOT NULL,
                                        PRIMARY KEY (`killID`,`groupName`,`groupNum`,`groupID`)
                                        ) ENGINE=InnoDB");

                Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, groupID, points, total_price from zz_participants where $column = $id and isVictim = 1");
                Db::execute("replace into zz_stats (type, typeID, groupID, lost, pointsLost, iskLost) select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3");

                if ($calcKills) {
                        Db::execute("truncate table zz_stats_temporary");
                        Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, vGroupID, points, total_price from zz_participants where $column = $id and isVictim = 0");
                        Db::execute("insert into zz_stats (type, typeID, groupID, destroyed, pointsDestroyed, iskDestroyed) (select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3) on duplicate key update destroyed = values(destroyed), pointsDestroyed = values(pointsDestroyed), iskDestroyed = values(iskDestroyed)");
                }

                Db::execute("drop table if exists zz_stats_temporary");
        }

}

