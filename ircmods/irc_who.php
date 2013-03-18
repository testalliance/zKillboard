<?php

class irc_who implements ircCommand {
	public function getRequiredAccessLevel() {
		return 0;
	}

	public function getDescription() {
		return "Who search!";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		$cmd = $parameters[0];
		$url = "http://evewho.com";
		if($cmd == "corp")
		{
			unset($parameters[0]);
			$search = implode(" ", $parameters);
			$url = $url."/ek_corp.php?name=".urlencode($search);
			$res = json_decode(self::fetch($url), true);
			$info = array();
			if(isset($res))
				$info = Info::getCorpDetails($res["corporation_id"]);
			
			if(!is_null($info))
				irc_out("|g|".$info["corporationName"]."|n| (|g|".$info["corporationID"]."|n|) | |g|http://evewho.com/corp/".urlencode($info["corporationName"])."/|n| | |g|Kills: ".$info["shipsDestroyed"]."|n| | |r|Losses: ".$info["shipsLost"]."|n| | |g|zKB: https://zkillboard.com/corporation/".$info["corporationID"]."/|n|");
			else
				irc_out("|g|".$info["corporationName"]."|n| (|g|".$info["corporationID"]."|n|) | |g|http://evewho.com/corp/".urlencode($info["corporationName"])."/|n|");
		}
		elseif($cmd == "alli")
		{
			unset($parameters[0]);
			$search = implode(" ", $parameters);
			$res = Info::getAlliId($search);
			$info = array();
			if(isset($res))
				$info = Info::getAlliDetails($res);

			if(!is_null($info))
			{
				irc_out("|g|".$info["allianceName"]."|n| (|g|".$info["allianceID"]."|n|) | |g|http://evewho.com/alli/".urlencode($info["allianceName"])."/|n| | |g|Kills: ".$info["shipsDestroyed"]."|n| | |r|Losses: ".$info["shipsLost"]."|n| | |g|zKB: https://zkillboard.com/alliance/".$info["allianceID"]."/|n|");
			}
			else
				irc_out("Alliance |r|not|n| found");
		}
		else
		{
			$search = implode(" ", $parameters);
			$url = $url."/ek_pilot.php?name=".urlencode($search);
			$res = json_decode(self::fetch($url), true);
			// Lets try and find more info
			$info = array();
			if(isset($res["character_id"]))
				$info = Info::getPilotDetails($res["character_id"]);
			
			if(!is_null($res))
			{
				if(!is_null($info))
					irc_out("|g|".$res['name']." |n|(|g|".$res['character_id']."|n|) | |g|http://evewho.com/pilot/". urlencode($res["name"])."/|n| | |g|Kills: ".$info["shipsDestroyed"]."|n| | |r|Losses: ".$info["shipsLost"]."|n| | |g|zKB: https://zkillboard.com/character/".$res["character_id"]."/");
				else
					irc_out("|g|".$res['name']." |n|(|g|".$res['character_id']."|n|) | |g|http://evewho.com/pilot/". urlencode($res["name"])."/");
			}
			else
				irc_out("Character |r|not|n| found");
		}
	}

	private function fetch($url)
	{
		$userAgent = "ESCBot IRC Lookup";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl, CURLOPT_ENCODING, "");
		$headers = array();
		$headers[] = "Connection: keep-alive";
		$headers[] = "Keep-Alive: timeout=10, max=1000";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		$errno = curl_errno($curl);
		$error = curl_error($curl);

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($httpCode >= 400)
			irc_out("Error with request: $httpCode, $url");
		return $result;
	}
	public function isHidden() { return false; }
}
