<?php
class mDetect
{
	public static function isMobile()
	{
		$detect = new Mobile_Detect();
		$check = $detect->isMobile();
		if($check)
			return true;
		else
			return false;
	}
	public static function isTablet()
	{
		$detect = new Mobile_Detect();
		$check = $detect->isTablet();
		if($check)
			return true;
		else
			return false;
	}
}
