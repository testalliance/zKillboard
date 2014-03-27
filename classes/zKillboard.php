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

class zKillboard
{
	public static function analytics()
	{
		$html = "
			<script>
			  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			  ga('create', 'UA-49428449-1', 'zkillboard.com');
			  ga('send', 'pageview');

			</script>
			";
		return $html;
	}

        public static function top()
        {
		$html = '
			<script type="text/javascript"><!--
			google_ad_client = "ca-pub-7481220870937701";
			/* zKillboard */
			google_ad_slot = "3186136253";
			google_ad_width = 728;
			google_ad_height = 90;
			//-->
			</script>
			<script type="text/javascript"
			src="//pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
		';
                return $html;
        }

        public static function bottom()
        {
		return static::top();
        }

        public static function mobileTop()
        {
                return static::mobileBottom();
        }

        public static function mobileBottom()
        {
                $html = '<script type="text/javascript"><!--
                google_ad_client = "ca-pub-8111276931546791";
                /* zkb mobile bottom */
                google_ad_slot = "9924725708";
                google_ad_width = 320;
                google_ad_height = 50;
                //-->
                </script>
                <script type="text/javascript"
                src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
                </script>';
                return $html;
        }

        public static function igbTop()
        {
		return static::top();
        }

        public static function igbBottom()
        {
		return static::top();
        }
}
