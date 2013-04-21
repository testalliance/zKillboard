<?php

class IP
{
	public static function get()
	{
		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
		elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else $ip = $_SERVER['REMOTE_ADDR'];

		return $ip;
	}
}