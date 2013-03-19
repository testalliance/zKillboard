<?php
require_once dirname(__FILE__) . "/../init.php";
$logging = true;

global $stompServer, $stompUser, $stompPassword;

$stomp = new Stomp($stompServer, $stompUser, $stompPassword);
$destination = "/topic/kills";
$kills = $stomp->subscribe($destination);

while(true)
{
	$frame = $stomp->readFrame();
	if($frame)
	{
		$killdata = json_decode($frame->body, true);
		var_dump($killdata);
		if(!empty($killdata))
		{
			$killID = $killdata["killID"];
			// Check if the killID is already posted (It's always API, so no need to worry about manual mails sneaking in)
			$count = Db::queryField("SELECT count(1) count FROM zz_killmails WHERE killID = :killID LIMIT 1", "count", array(":killID" => $killID), 0);
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
		else
		{
			if($logging)
				Log::log($frame->headers["message-id"].":: frame empty");
			$stomp->ack($frame);
			continue;
		}
	}
}
