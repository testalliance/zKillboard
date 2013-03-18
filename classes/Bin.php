<?php

/**
 * Extremely temporary storage.
 */
class Bin
{
	private static $bin = array();

	/**
	 * get something from storage
	 *
	 * @static
	 * @param $name
	 * @param null $default
	 * @return null
	 */
	public static function get($name, $default = null)
	{
		if (isset(Bin::$bin[$name])) return Bin::$bin[$name];
		return $default;
	}

	/**
	 * Store something
	 *
	 * @static
	 * @param $name
	 * @param $value
	 * @return void
	 */
	public static function set($name, $value)
	{
		Bin::$bin[$name] = $value;
	}
}
