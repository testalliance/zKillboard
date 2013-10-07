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
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:728px;height:90px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="3776014371"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}

	public static function bottom()
	{
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:728px;height:90px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="5039094775"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}
	
	public static function mobileTop()
	{
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:320px;height:50px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="9932221977"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}

	public static function mobileBottom()
	{
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:320px;height:50px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="2408955178"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}

	public static function igbTop()
	{
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:728px;height:90px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="5502022370"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}

	public static function igbBottom()
	{
		$html = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<ins class="adsbygoogle"
			style="display:inline-block;width:728px;height:90px"
			data-ad-client="ca-pub-8111276931546791"
			data-ad-slot="5502022370"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>';

		return $html;
	}
}
