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

class Cache
{
	// i just want to make sure....
	function Cache()
	{
		trigger_error('The class "cache" may only be invoked statically.', E_USER_ERROR);
	}

	protected static function getMC()
	{
		global $mc, $memcacheServer, $memcachePort;

		if ($mc == null) {
			if(extension_loaded("Memcache") && !extension_loaded("Memcached"))
			{
				$mc = new MemCache();
				$mc->connect($memcacheServer, $memcachePort);
			}
			elseif(extension_loaded("Memcached") && !extension_loaded("Memcache"))
			{
				$mc = new Memcached();
				$mc->addServer($memcacheServer, $memcachePort);
			}
			else
			{
				// If both are loaded, we just use Memcached
				$mc = new Memcached();
				$mc->addServer($memcacheServer, $memcachePort);
			}
		}
		return $mc;
	}

	// Define 1 hour std expiry time for objects
	public static function set($key, $value, $timeout = '3600')
	{
		$mc = Cache::getMC();

		// This is silly, but seeing as replace and set for memcache wants to know if it should package it or not, and memcached doesn't want to know, this is how it has to be :\
		if(extension_loaded("Memcache") && !extension_loaded("Memcached"))
		{
			$result = $mc->replace($key, $value, 0, time() + $timeout);
			if ($result === FALSE)
				$result = $mc->set($key, $value, 0, time() + $timeout);
			if ($result !== TRUE && $result !== FALSE)
				return false;
			return true;
		}
		elseif(extension_loaded("Memcached") && !extension_loaded("Memcache"))
		{
			$result = $mc->replace($key, $value, time() + $timeout);
			if ($result === FALSE)
				$result = $mc->set($key, $value, time() + $timeout);
			if ($result !== TRUE && $result !== FALSE)
				return false;
			return true;
		}
		else
		{
			$result = $mc->replace($key, $value, time() + $timeout);
			if ($result === FALSE)
				$result = $mc->set($key, $value, time() + $timeout);
			if ($result !== TRUE && $result !== FALSE)
				return false;
			return true;
		}
	}

	public static function get($key)
	{
		$mc = Cache::getMC();
		$value = $mc->get($key);
		return $value;
	}

	// Erases a key after [$timeout] seconds
	// if that key exists
	public static function delete($key, $timeout = 0)
	{
		$mc = Cache::getMC();
		return $mc->delete($key, $timeout);
	}

	public static function increment($key, $timeout = 3600)
	{
		$mc = Cache::getMC();
		$mc->add($key, 0, 0, $timeout);
		return $mc->increment($key);
	}

	public static function decrement($key, $timeout = 3600)
	{
		$mc = Cache::getMC();
		$mc->add($key, 0, $timeout);
		return $mc->decrement($key);
	}
}
