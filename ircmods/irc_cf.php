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

class irc_cf implements ircCommand {
	public function getRequiredAccessLevel() {
		return 10;
	}

	public function getDescription() {
		return "CloudFlare API. Usage: Bandwidth used: |g|.z cf bw|n| / Pageviews: |g|.z cf pv|n| / Purge a file: |g|.z cf purge <url>|n| / Devmode: |g|.z cf devmode on/off|n|";
	}

	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel) {
		global $cfUser, $cfKey;
		$cf = new CloudFlare($cfUser, $cfKey);
		$cmd = $parameters[0];
		unset($parameters[0]);
		$text = implode(" ", $parameters);
		
		if($cmd == "devmode")
		{
			if($text == "on")
				$mode = 1;
			elseif($text == "off")
				$mode = 0;
			else
			{
				irc_out("Invalid Mode"); die();
			}
			$response = $cf->devmode($mode, "zkillboard.com");
			$result = $response["response"]["fb_msg"];
			
			if(stristr($result, "enabled"))
				irc_out("|g|$result|n|");
			else
			{
				$result = str_replace("disabled", "|r|disabled|g|", $result);
				irc_out("|g|$result|n|");
			}
		}
		if($cmd == "purge")
		{
			if(!is_null($text))
			{
				$response = $cf->purge_file($text, "zkillboard.com");
				$result = $response["result"];
				if($result == "success")
					irc_out("Purge of file: |g|$text|n| ended with: |g|$result|n|");
				else
					irc_out("Purge of file: |g|$text|n| ended with: |r|$result|n|");
			}
			else
			{
				irc_out("You need to specify a URL to purge");
			}
		}
		if($cmd == "pv")
		{
			$response = $cf->stats("zkillboard.com", 120);
			$pvreg = $response["response"]["result"]["objs"][0]["trafficBreakdown"]["pageviews"]["regular"];
			$pvthreat = $response["response"]["result"]["objs"][0]["trafficBreakdown"]["pageviews"]["threat"];
			$pvcrawler = $response["response"]["result"]["objs"][0]["trafficBreakdown"]["pageviews"]["crawler"];
			$pvtotal = $pvreg + $pvthreat + $pvcrawler;
			irc_out("Pageviews for the last 6 hours: Total: |g|$pvtotal|n| (Regular: |g|$pvreg|n|, Threats: |r|$pvthreat|n|, Crawlers: |g|$pvcrawler|n|)");
		}
		if($cmd == "bw")
		{
			$response = $cf->stats("zkillboard.com", 120);
			
			$bwz = $response["response"]["result"]["objs"][0]["bandwidthServed"]["user"] / 1024 / 1024;
			$bwcf = $response["response"]["result"]["objs"][0]["bandwidthServed"]["cloudflare"] / 1024 / 1024;
			$bwtotal = $bwz + $bwcf;
			irc_out("BW Used for the last 6 hours. Total: |g|" . number_format($bwtotal, 2, ",", ".") . "GB|n|, zKB: |g|" . number_format($bwz, 2, ",", ".") . "GB|n|, CF: |g|".number_format($bwcf, 2, ",", ".")."GB|n|");
		}
	}
    public function isHidden() { return false; }
}
