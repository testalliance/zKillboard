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

class DNA {
	public static function gettext($array,$ship){
	$goodspots = array("High Slots","Rigs","Low Slots","Mid Slots");
	$fit="";
	$fit.= $ship.":";
	foreach($array as $item){
		if (in_array($item["flagName"] ,$goodspots)){
		//print_r($item);
		if (($item["qtyDropped"] + $item["qtyDestroyed"])>1){
			$fit .= $item["typeID"].";".($item["qtyDropped"] + $item["qtyDestroyed"]).":";
		}else{
			$fit.= $item["typeID"].":";
		}	
		}
	//	}
	}
	$fit .= ":";
	return $fit;
	}
}
?>
