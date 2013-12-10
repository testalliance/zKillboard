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

class Price
{
	public static function setPrice($typeID, $price)
	{
		return Db::execute("replace into zz_prices (typeID, price) values (:typeID, :price)", array(":typeID" => $typeID, ":price" => $price));
	}

	public static function updatePrice($killID, $tempTables = false)
	{
		$temp = $tempTables ? "_temporary" : "";
		$items = Db::query("select typeID, qtyDropped, qtyDestroyed from zz_items$temp where killID = :killID", array(":killID" => $killID), 0);
		$shipTypeID = Db::queryField("select shipTypeID from zz_participants$temp where isVictim = 1 and killID = :killID", "shipTypeID", array(":killID" => $killID), 0);

		$total = $shipTypeID ? self::getItemPrice($shipTypeID) : 0;
		if ($items) foreach ($items as $item) {
			$typeID = $item["typeID"];
			$price = self::getItemPrice($typeID);
			$total += $price * ($item["qtyDropped"] + $item["qtyDestroyed"]);
			Db::execute("update zz_items$temp set price = :price where typeID = :typeID and killID = :killID",
									array(":typeID" => $typeID, ":killID" => $killID, ":price" => $price));
		}

		Db::execute("update zz_participants$temp set total_price = :total where killID = :killID", array(":killID" => $killID, ":total" => $total));

		return $total;
	}


	/**
	 * Obtain the price of an item.
	 *
	 * @static
	 * @param	$typeID int The typeID of the item
	 * @return double The price of the item.
	 */
	public static function getItemPrice($typeID)
	{
		if (in_array($typeID, array(588, 596, 601, 670, 606, 33328))) return 10000; // Pods and noobships
		if (in_array($typeID, array(25, 51, 29148, 3468))) return 1; // Male Corpse, Female Corpse, Bookmarks, Plastic Wrap

		$price = Price::getDatabasePrice($typeID);
		if ($price == 0) $price = Price::getMarketPrice($typeID);
		if ($price == 0) $price = Price::getMarketPrice($typeID, false);
		if ($price == 0) $price = Price::getItemBasePrice($typeID);
		if ($price == 0) $price = 0.01; // Give up

		return $price;
	}

	/**
	 * @static
	 * @param	$typeID int
	 * @return double The price of the typeID, if found.	Zero if not found.
	 */
	protected static function getDatabasePrice($typeID)
	{
		$price = Db::queryField("select price from zz_prices where typeID = :typeID", "price",
														array(":typeID" => $typeID));
		if ($price != null) return $price;
		return 0;
	}

	/**
	 * @static
	 * @param	$typeID int
	 * @return null
	 */
	protected static function getItemBasePrice($typeID)
	{
		// Market failed, faction pricing failed, do we have a basePrice in the database?
		$price = Db::queryField("select basePrice from ccp_invTypes where typeID = :typeID", "basePrice", array(":typeID" => $typeID));
		Price::storeItemPrice($typeID, $price);
		if ($price != null) return $price;
	}

	/**
	 * Get the market price of the item in Jita.
	 * Attempts to use the sell median price first, if that value isn't set it will use
	 * the median of all values.
	 *
	 * @param int $typeID
	 * @return double
	 */
	public static function getMarketPrice($typeID, $useJita = false)
	{
		if ($useJita) {
			// Get the Jita price.
			$eveCentralApi = "http://api.eve-central.com/api/marketstat?usesystem=30000142&typeid=$typeID";
		} else {
			$eveCentralApi = "http://api.eve-central.com/api/marketstat?typeid=$typeID";
		}
		$result = Cache::get($eveCentralApi);
		if ($result === FALSE) {
			try {
				Util::getPheal();
				$result = Pheal::request_http_curl($eveCentralApi, array());
				Cache::set($eveCentralApi, $result, 600);
			} catch (Exception $ex) {
				return 0;
			}
		}
		try {
			$xml = new SimpleXMLElement($result);
			@$sellMedian = (double)$xml->marketstat->type->sell->median;
			@$allMedian = (double)$xml->marketstat->type->all->median;
			if ($allMedian == 0) $allMedian = 0.00001;
			$price = $allMedian;
			if ($price == 0 || ($sellMedian / $allMedian) > 2) $price = $sellMedian;
			if ($price !== null && $price >= 0.01) {
				Price::storeItemPrice($typeID, $price);
				return $price;
			}
		} catch (Exception $ex) {

		}
		//if ($useJita == false) return getMarketPrice($typeID, true);
		return 0;
	}

	/**
	 * Save the price in the database for future reference.
	 * Price will expire after 7 days.
	 *
	 * @static
	 * @param	$typeID
	 * @param	$price
	 * @return void
	 */
	protected static function storeItemPrice($typeID, $price)
	{
		if ($price == null) return;
		// 604800 is the number of seconds in 7 days

		//Log::log("Storing $typeID at price $price");
		Db::execute("replace into zz_prices (typeID, price, expires) values (:typeID, :price, date_add(now(), interval 7 day))",
				array(
					":price" => $price,
					":typeID" => $typeID,
					));
	}

}
