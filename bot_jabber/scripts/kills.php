<?php

class jab_kills implements jabCommand {
	public function getDescription() {
		return "Returns the stats for a pilot in Raiden. (From EVE-KILL). Usage .kills <name> / Example: .kills Karbowiak";
	}

	public function execute($nick, $command, $parameters)
    {
        if(sizeof($parameters) >= 1)
        {
            $search = implode(" ", $parameters);
            $id = Info::getCharId($search);
            if($id > 0)
            {
                $info = Info::getPilotDetails($id);
                $msg = "$info[characterName] ($info[corporationName] / $info[allianceName]) / Kills: $info[shipsDestroyed] / Losses: $info[shipsLost] / Overall Rank: ".$info["ranks"]["overallRank"]." / Recent Rank (Last 90 days) ".$info["ranks"]["recentRank"]." / Link: http://zkillboard.com/character/$id/";;
                return $msg;
            }
            else
            {
                return "Error, no such pilot found";
            }
        }
	}
    public function isHidden() { return true; }
}
