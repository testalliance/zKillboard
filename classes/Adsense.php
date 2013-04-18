<?php

/**
 * Self documenting code.
 *
 * If you don't get it, move along.
 */
class Adsense
{
	public static function top()
	{
		$html = '<script type="text/javascript">
		google_ad_client = "ca-pub-8111276931546791";
		/* eve-kill */
		google_ad_slot = "3776014371";
		google_ad_width = 728;
		google_ad_height = 90;
		</script>
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		return $html;
	}

	public static function bottom()
	{
		$html = '<script type="text/javascript">
		google_ad_client = "ca-pub-8111276931546791";
		/* eve-kill */
		google_ad_slot = "5039094775";
		google_ad_width = 728;
		google_ad_height = 90;
		</script>
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		return $html;
	}
	
	public static function mobile()
	{
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-8111276931546791";
		/* eve-kill */
		google_ad_slot = "3776014371";
		google_ad_width = 728;
		google_ad_height = 90;
		</script>
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
	}
}
