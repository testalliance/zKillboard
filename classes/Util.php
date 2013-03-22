<?php
class Util
{
	public static function setSubdomainGlobals($key, $row, $type)
	{
		global $subDomainKey, $subDomainRow;
		$subDomainKey = $key;
		$row["type"] = $type;
		$subDomainRow = $row;
		return true;
	}

	public static function isValidSubdomain($subDomain)
	{
		if ($subDomain === null || trim($subDomain) == "") return true;

		$subDomain = str_replace("_", " ", $subDomain);
		$array = array(":subDomain" => $subDomain);
		$row = Db::queryRow("select factionID, name from zz_factions where ticker = :subDomain", $array, 3600);
		if ($row != null) return Util::setSubdomainGlobals("factionID", $row, "faction");
		$row = Db::queryRow("select allianceID, name from zz_alliances where ticker = :subDomain", $array, 3600);
		if ($row != null) return Util::setSubdomainGlobals("allianceID", $row, "alliance");
		return false;
	}

	public static function isMaintenanceMode()
	{
		return "true" == Db::queryField("select contents from zz_storage where locker = 'maintenance'", "contents", array(), 0);
	}


	public static function getPheal($keyID = null, $vCode = null)
	{
		PhealConfig::getInstance()->http_method = "curl";
		PhealConfig::getInstance()->http_user_agent = "zKillboard API Fetcher (Pheal)";
		PhealConfig::getInstance()->http_post = false;
		PhealConfig::getInstance()->http_keepalive = true; // default 15 seconds
		PhealConfig::getInstance()->http_keepalive = 10; // KeepAliveTimeout in seconds
		PhealConfig::getInstance()->http_timeout = 30;
		PhealConfig::getInstance()->cache = new PhealFileCache("/var/killboard/cache/");
		PhealConfig::getInstance()->log = new PhealLogger();
		PhealConfig::getInstance()->api_customkeys = true;
		PhealConfig::getInstance()->api_base = 'https://api.eveonline.com/'; // API proxy server, to get everything gzipped.

		if ($keyID != null && $vCode != null) $pheal = new Pheal($keyID, $vCode);
		else $pheal = new Pheal();
		return $pheal;
	}

	public static function pluralize($string)
	{
		if (!Util::endsWith($string, "s")) return $string . "s";
		else return $string . "es";
	}

	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	public static function endsWith($haystack, $needle)
	{
		return substr($haystack, -strlen($needle)) === $needle;
	}

	public static function firstUpper($str)
	{
		if (strlen($str) == 1) return strtoupper($str);
		$str = strtolower($str);
		return strtoupper(substr($str, 0, 1)) . substr($str, 1);
	}

	public static function getKillHash($killID = null, $kill = null)
	{
		if ($killID != null) {
			$json = Db::queryField("select kill_json from zz_killmails where killID = :killID", "kill_json", array(":killID" => $killID), 0);
			if ($json === null) throw new Exception("Cannot find kill $killID");
			$kill = json_decode($json);
			if ($kill === null) throw new Exception("Cannot json_decode $killID");
		}
		if ($kill === null) throw new Exception("Can't hash an empty kill");

		$hashStr = "";
		$hashStr .= ":$kill->killTime:$kill->solarSystemID:$kill->moonID:";
		$victim = $kill->victim;
		$hashStr .= ":$victim->characterID:$victim->shipTypeID:$victim->damageTaken:";

		return hash("sha256", $hashStr);
	}

	public static function calcX($slot, $size)
	{
		$angle = $slot * (360 / 32) - 4;
		$rad = deg2rad($angle);
		$radius = $size / 2;
		return (int)(($radius * cos($rad)));
	}

	public static function calcY($slot, $size)
	{
		$angle = $slot * (360 / 32) - 4;
		$rad = deg2rad($angle);
		$radius = $size / 2;
		return (int)(($radius * sin($rad)));
	}

	private static $formatIskIndexes = array("", "k", "m", "b", "t", "tt", "ttt");

	public static function formatIsk($value)
	{
		$numDecimals = (((int)$value) == $value) && $value < 10000 ? 0 : 2;
		if ($value == 0) return number_format(0, $numDecimals);
		if ($value < 10000) return number_format($value, $numDecimals);
		$iskIndex = 0;
		while ($value > 999.99) {
			$value /= 1000;
			$iskIndex++;
		}
		return number_format($value, $numDecimals) . self::$formatIskIndexes[$iskIndex];
	}

	public static function convertUriToParameters()
	{
		$parameters = array();
		@$uri = $_SERVER["REQUEST_URI"];
		$split = explode("/", $uri);
		$currentIndex = 0;
		foreach ($split as $key) {
			$value = $currentIndex + 1 < sizeof($split) ? $split[$currentIndex + 1] : null;
			switch ($key) {
				case "kills":
				case "losses":
				case "w-space":
				case "solo":
					$parameters[$key] = true;
					break;
				case "character":
				case "characterID":
				case "corporation":
				case "corporationID":
				case "alliance":
				case "allianceID":
				case "faction":
				case "factionID":
				case "ship":
				case "shipID":
				case "group":
				case "groupID":
				case "system":
				case "systemID":
				case "region":
				case "regionID":
					if ($value != null) {
						if (strpos($key, "ID") === false) $key = $key . "ID";
						if ($key == "systemID") $key = "solarSystemID";
						else if ($key == "shipID") $key = "shipTypeID";
						$exploded = explode(",", $value);
						if (sizeof($exploded) > 10) throw new Exception("Too many IDs! Max: 10");
						$parameters[$key] = $exploded;
					}
					break;
				case "page":
					$value = (int)$value;
					if ($value < 1) throw new Exception("page must be greater than or equal to 1");
					$parameters[$key] = $value;
					break;
				case "orderDirection":
					if (!($value == "asc" || $value == "desc")) throw new Exception("Invalid orderDirection!  Allowed: asc, desc");
					$parameters[$key] = $value;
					break;
				case "pastSeconds":
					$value = (int) $value;
					if (($value / 86400) > 7) throw new Exception("pastSeconds is limited to a max of 7 days");
					$parameters[$key] = $value;
					break;
				case "limit":
					$value = (int) $value;
					if ($value < 200) $parameters["limit"] = $value;
					break;
				default:
					if (is_numeric($value) && $value < 0) throw new Exception("$value is not a valid entry for $key");
					/*if ((int) $key != $key)*/ $parameters[$key] = $value;
			}
			$currentIndex++;
		}
		return $parameters;
	}

	public static function shortString($string, $maxLength = 8)
	{
		if (strlen($string) <= $maxLength) return $string;
		return substr($string, 0, $maxLength - 3) . "...";
	}

	public static function truncate($str, $length = 200, $trailing = "...")
	{
		$length -= mb_strlen($trailing);
		if (mb_strlen($str) > $length) {
			// string exceeded length, truncate and add trailing dots
			return mb_substr($str, 0, $length) . $trailing;
		}
		else
		{
			// string was already short enough, return the string
			$res = $str;
		}
		return $res;
	}

	public static function pageTimer()
	{
		global $timer;
		return $timer->stop();
	}

	public static function isActive($pageType, $currentPage, $retValue = "active")
	{
		return strtolower($pageType) == strtolower($currentPage) ? $retValue : "";
	}

	private static $months = array("", "JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC");

	public static function getMonth($month)
	{
		return self::$months[$month];
	}

	private static $longMonths = array("", "January", "February", "March", "April", "May", "June", "July", "August",
			"September", "October", "November", "December");

	public static function getLongMonth($month)
	{
		return self::$longMonths[$month];
	}
}
