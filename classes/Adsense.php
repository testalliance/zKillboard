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
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-6702479302366531";
		/* eve-kill */
		google_ad_slot = "3192421769";
		google_ad_width = 728;
		google_ad_height = 90;
		</script>
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		return $html;
	}

	public static function bottom()
	{
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-6702479302366531";
		/* eve-kill */
		google_ad_slot = "3192421769";
		google_ad_width = 728;
		google_ad_height = 90;
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
		return $html;
	}
	
	public static function mobile()
	{
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-6702479302366531";
		/* eve-kill */
		google_ad_slot = "3192421769";
		google_ad_width = 728;
		google_ad_height = 90;
		</script>
		<script type="text/javascript" src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
	}
}
