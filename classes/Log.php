<?php

class Log
{

	public function __construct()
	{
		trigger_error('The class "log" may only be invoked statically.', E_USER_ERROR);
	}

	public static function log($text)
	{
		global $logfile;
		if (!file_exists($logfile) && !is_writable(dirname($logfile))) return; // Can't create the file
		if (is_writable($logfile)) error_log(date("Ymd H:i:s") . " $text \n", 3, $logfile);
	}

	/*
	   Mapped by Eggdrop to log into #esc
	 */
	public static function irc($text, $from = "zkillboard - ")
	{
		$text = Log::addIRCColors($text);
		error_log("\n${from}$text\n", 3, "/var/killboard/bot/esc.txt");
	}


	public static function ircAdmin($text, $from = "zkillboard - ")
	{
		$text = Log::addIRCColors($text);
		error_log("\n${from}$text\n", 3, "/var/killboard/bot/escadmin.txt");
	}

	public static function error($text)
	{
		error_log(date("Ymd H:i:s") . " $text \n", 3, "/var/log/kb/kb_error.log");
	}

	public static $colors = array(
		"|r|" => "\x0305", // red
		"|g|" => "\x0303", // green
		"|w|" => "\x0300", // white
		"|b|" => "\x0302", // blue
		"|blk|" => "\x0301", // black
		"|c|" => "\x0310", // cyan
		"|y|" => "\x0308", // yellow
		"|n|" => "\x03", // reset
	);

	public static function addIRCColors($msg)
	{
		foreach (Log::$colors as $color => $value) {
			$msg = str_replace($color, $value, $msg);
		}
		return $msg;
	}

	public static function stripIRCColors($msg)
	{
		foreach (Log::$colors as $color => $value) {
			$msg = str_replace($color, "", $msg);
		}
		return $msg;
	}
}

/*
Bold: \002text\002
Underline: \037text\037

Start and end with \003

White: \0030text\003
\0030: white
\0031: black
\0032: blue
\0033: green
\0034: light red
\0035: brown
\0036: purple
\0037: orange
\0038: yellow
\0039: light green
\0310: cyan
\0311: light cyan
\0312: light blue
\0313: pink
\0314: gr
\0315: light grey
 */
