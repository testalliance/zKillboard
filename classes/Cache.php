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

/**
 * zKillboard cache class
 */
class Cache
{
	function Cache()
	{
		trigger_error('The class "cache" may only be invoked statically.', E_USER_ERROR);
	}

	/**
	 * Initiate the cache
	 */
	protected static function getCache()
	{
		global $cache, $memcacheServer, $memcachePort;

		if ($cache == null) {
			if(extension_loaded("Memcache"))
			{
				$cache = new MemCache();
				$cache->connect($memcacheServer, $memcachePort);
			}
			elseif(extension_loaded("Memcached"))
			{
				$cache = new Memcached();
				$cache->addServer($memcacheServer, $memcachePort);
			}
			else
			{
				$cache = new FileCache();
				$cache->cacheDir = "cache/queryCache/";
			}
		}
		return $cache;
	}

	/**
	 * Sets data to the cache
	 * 
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * @return bool
	 */
	public static function set($key, $value, $timeout = '3600')
	{
		$cache = Cache::getCache();

		// This is silly, but seeing as replace and set for memcache wants to know if it should package it or not, and memcached doesn't want to know, this is how it has to be :\
		if(extension_loaded("Memcache"))
		{
			$result = $cache->replace($key, $value, 0, time() + $timeout);
			if ($result === FALSE)
				$result = $cache->set($key, $value, 0, time() + $timeout);
			if ($result !== TRUE && $result !== FALSE)
				return false;
			return true;
		}
		else
		{
			$result = $cache->replace($key, $value, time() + $timeout);
			if ($result === FALSE)
				$result = $cache->set($key, $value, time() + $timeout);
			if ($result !== TRUE && $result !== FALSE)
				return false;
			return true;
		}
	}

	/**
	 * Gets data from the cache
	 * 
	 * @param $key
	 * @return array
	 */
	public static function get($key)
	{
		$cache = Cache::getCache();
		$value = $cache->get($key);
		return $value;
	}

	/**
	 * Deletes data from the cache
	 * 
	 * @param $key
	 * @param $timeout (This only works for Memcached, file cache flat out ignores it)
	 * @return bool
	 */
	public static function delete($key, $timeout = 0)
	{
		$cache = Cache::getCache();
		return $cache->delete($key, $timeout);
	}

	/**
	 * Increment a value
	 * 
	 * @param $key
	 * @param $timeout (This only works for Memcached, file cache flat out ignores it)
	 */
	public static function increment($key, $timeout = 3600)
	{
		$cache = Cache::getCache();
		$cache->add($key, 0, 0, $timeout);
		return $cache->increment($key);
	}

	/**
	 * Decrement a value
	 * 
	 * @param $key
	 * @param $timeout (This only works for Memcached, file cache flat out ignores it)
	 */
	public static function decrement($key, $timeout = 3600)
	{
		$cache = Cache::getCache();
		$cache->add($key, 0, $timeout);
		return $cache->decrement($key);
	}
}
