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
