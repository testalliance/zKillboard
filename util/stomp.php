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

require_once dirname(__FILE__) . "/../init.php";

$logging = true;

global $stompServer, $stompUser, $stompPassword, $baseAddr;
use FuseSource\Stomp\Stomp;
$stomp = new Stomp($stompServer);
$stomp->connect($stompUser, $stompPassword);
$destination = "/topic/kills";
$kills = $stomp->subscribe($destination);

while(true)
{
	$frame = $stomp->readFrame();
	if($frame != NULL)
	{
		$killdata = json_decode($frame->body, true);
		if(!empty($killdata))
		{
			$killID = $killdata["killID"];
			$count = Db::queryField("SELECT count(1) AS count FROM zz_killmails WHERE killID = :killID LIMIT 1", "count", array(":killID" => $killID), 0);
			if($count == 0)
			{
				if($killID > 0)
				{
					$hash = Util::getKillHash(null, json_decode($frame->body));
					Db::execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
						array("killID" => $killID, ":hash" => $hash, ":source" => "stompQueue", ":json" => json_encode($killdata)));
					if($logging)
						Log::log($frame->headers["message-id"]."::$killID saved");
					$stomp->ack($frame);
					continue;
				}
				else
				{
					if($logging)
						Log::log($frame->headers["message-id"].":: killID is negative");
					$stomp->ack($frame);
					continue;
				}
			}
			else
			{
				if($logging)
					Log::log($frame->headers["message-id"]."::$killID exists");
				$stomp->ack($frame);
				continue;
			}
		}
	}
}