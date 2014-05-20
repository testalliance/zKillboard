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

class cli_stompSend implements cliCommand
{
        public function getDescription()
        {
                return "Sends out data via STOMP. |w|Beware, this is a persistent script. It's run and forget!.|n| Usage: |g|stompSend";
        }

        public function getAvailMethods()
        {
                return ""; // Space seperated list
        }

        public function getCronInfo()
        {
                global $stompUser;
                return class_exists("Stomp") && $stompUser != "guest" ? array(55 => "") : array();
        }


        public function execute($parameters, $db)
        {
                global $stompServer, $stompUser, $stompPassword;

                // Ensure the class exists
                if (!class_exists("Stomp")) {
                        die("ERROR! Stomp not installed!  Check the README to learn how to install Stomp...\n");
                }

                $stomp = new Stomp($stompServer, $stompUser, $stompPassword);

                $stompKey = "StompSend::lastFetch";
                $lastFetch = date("Y-m-d H:i:s", time() - (12 * 3600));
                $lastFetch = Storage::retrieve($stompKey, $lastFetch);
                $stompCount = 0;

                $timer = new Timer();
                while ($timer->stop() < 55000)
                {
                        if (Util::isMaintenanceMode()) return;
                        $result = $db->query("SELECT killID, insertTime FROM zz_killmails WHERE insertTime > :lastFetch AND processed > 0 ORDER BY killID limit 1000", array(":lastFetch" => $lastFetch), 0);
                        foreach($result as $kill)
                        {
                                $json = Killmail::get($kill["killID"]);
                                $lastFetch = max($lastFetch, $kill["insertTime"]);
                                if(!empty($json))
                                {
                                        if($kill["killID"] > 0)
                                        {
                                                $stompCount++;
                                                $destinations = self::getDestinations($json);
                                                foreach ($destinations as $destination)
                                                {
                                                        $stomp->send($destination, $json);
                                                }
                                        }
                                        $data = json_decode($json, true);
                                        $map = json_encode(array("solarSystemID" => $data["solarSystemID"], "killID" => $data["killID"], "characterID" => $data["victim"]["characterID"], "corporationID" => $data["victim"]["corporationID"], "allianceID" => $data["victim"]["allianceID"], "shipTypeID" => $data["victim"]["shipTypeID"], "killTime" => $data["killTime"], "involved" => count($data["attackers"]), "totalValue" => $data["zkb"]["totalValue"], "pointsPrInvolved" => $data["zkb"]["points"]));
                                        $stomp->send("/topic/starmap.systems.active", $map);
                                }
                        }
                        Storage::store($stompKey, $lastFetch);
                        sleep(5);
                }
                if($stompCount > 0) Log::log("Stomped $stompCount killmails");
        }

        private function getDestinations($kill)
        {
                $kill = json_decode($kill, true);
                $destinations = array();

                $destinations[] = "/topic/kills";
                $destinations[] = "/topic/location.solarsystem.".$kill["solarSystemID"];

                // victim
                if($kill["victim"]["characterID"] > 0) $destinations[] = "/topic/involved.character.".$kill["victim"]["characterID"];
                if($kill["victim"]["corporationID"] > 0) $destinations[] = "/topic/involved.corporation.".$kill["victim"]["corporationID"];
                if($kill["victim"]["factionID"] > 0) $destinations[] = "/topic/involved.faction.".$kill["victim"]["factionID"];
                if($kill["victim"]["allianceID"] > 0) $destinations[] = "/topic/involved.alliance.".$kill["victim"]["allianceID"];

                // attackers
                foreach($kill["attackers"] as $attacker)
                {
                        if($attacker["characterID"] > 0) $destinations[] = "/topic/involved.character." . $attacker["characterID"];
                        if($attacker["corporationID"] > 0) $destinations[] = "/topic/involved.corporation." . $attacker["corporationID"];
                        if($attacker["factionID"] > 0) $destinations[] = "/topic/involved.faction." . $attacker["factionID"];
                        if($attacker["allianceID"] > 0) $destinations[] = "/topic/involved.alliance." . $attacker["allianceID"];
                }

                return $destinations;
        }
}