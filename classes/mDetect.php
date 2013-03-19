<?php
class mDetect
{
	public static function isMobile()
	{
		require_once( "vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php" );
		$detect = new Mobile_Detect();
		$check = $detect->isMobile();
		if($check)
			return true;
		else
			return false;
	}
	public static function isTablet()
	{
		require_once( "vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php" );
		$detect = new Mobile_Detect();
		$check = $detect->isTablet();
		if($check)
			return true;
		else
			return false;
	}
}
