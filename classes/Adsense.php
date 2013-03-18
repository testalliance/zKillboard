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
		google_ad_client = "ca-pub-8111276931546791";
		/* Eve-Kill topad */
		google_ad_slot = "6395756741";
		google_ad_width = 728;
		google_ad_height = 90;
		//-->
		</script>
		<script type="text/javascript"
		src="//pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>';
		return $html;
	}

	public static function bottom()
	{
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-8111276931546791";
		/* Eve-Kill bottom ad */
		google_ad_slot = "7350364054";
		google_ad_width = 728;
		google_ad_height = 90;
		//-->
		</script>
		<script type="text/javascript"
		src="//pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>';
		return $html;
	}
	
	public static function mobile()
	{
		$html = '<script type="text/javascript"><!--
		google_ad_client = "ca-pub-8111276931546791";
		/* Mobile */
		google_ad_slot = "6441349977";
		google_ad_width = 320;
		google_ad_height = 50;
		//-->
		</script>
		<script type="text/javascript"
		src="//pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>';
	}
}
