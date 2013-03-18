<?php
include_once('SmartIRC.php');
include_once('SmartIRC/defines.php');
include_once('SmartIRC/irccommands.php');
include_once('SmartIRC/messagehandler.php');
require_once( "/storage/www/zkb/init.php" );

$channels = array("#esc", "#escadmin");
$nicks = array("Karbowiak", "Squizz_C");
$nick = "ESCphp";

/*Next, create the bot-class:*/

class mybot
{
	/*Quit Function*/
	function quit(&$irc, &$data)
	{
		global $nicks;
		// Only run the command if the nick is an owner.
		if(in_array($data->nick, $nicks)) {
			exit(); 
			Return ;
		}
	}

	/*Kick-Function*/

	function kick(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1],$data->messageex[2]))
			{
				$nickname = $data->messageex[1];
				$reason = $data->messageex[2];
				$channel = $data->channel;
				$irc->kick($channel, $nickname, $reason);
			}
			else
			{
				$irc->message( $data->type, $data->nick, 'Invalid Parameter' );
				$irc->message( $data->type, $data->nick, 'use: !kick $nick' );
			}
		}
	}

	/*If the bot gets kicked, let it rejoin*/

	function kick_response(&$irc, &$data)
	{ 
		global $nicks;
		sleep(2);
		$irc->join($data->channel); 
		Return ;
	}


	/*Function to change channelmodes*/

	function mode($channel, $newmode = null, $priority = SMARTIRC_MEDIUM) 
	{
		if ($newmode !== null)
		{
			$irc->_send('MODE '.$channel.' '.$newmode, $priority);
		}
		else
		{ 
			$irc->_send('MODE '.$channel, $priority);
		}
	}

	/*Devoice Function*/

	function devoice(&$irc, &$data) 
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{
				$nickname = $data->messageex[1];
				$channel = $data->channel;  
				$irc->devoice($channel, $nickname );
			}
		}
	}

	/*Op Function*/

	function op(&$irc, &$data)  
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{    
				$nickname = $data->messageex[1];
				$channel = $data->channel;  
				$irc->op($channel, $nickname );
			}
		}
	}

	/*Deop Function*/

	function deop(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{
			$nickname = $data->messageex[1];
			$channel = $data->channel;  
			$irc->deop($channel, $nickname );
			}
		}
	}

	/*Join Function*/

	function join(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{    
				$channel = $data->messageex[1];
				$irc->join($channel);
			}
		}      
	}

	/*Part Function*/

	function part(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{    
				$channel = $data->messageex[1];
				$irc->part($channel);
			}
		}      
	}

	/*Function to rejoin a channel*/

	function rejoin(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks))
		{
			if(isset($data->messageex[1]))
			{    
				$channel = $data->messageex[1];
				$irc->part($channel);
				$irc->join($channel);
			}
		}      
	}

	/*Ban Function*/
	function ban(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks)) {
			if(isset($data->messageex[1]))
			{
				$hostmask = $data->messageex[1];    
				$channel = $data->channel;
				$irc->ban($channel, $hostmask);
			}
			else
			{
				$irc->message( $data->type, $data->nick, 'Invalid Parameter' );
				$irc->message( $data->type, $data->nick, 'use: !ban $nick' );
			}
		}
	}

	/*Function for the nickchange-command*/

	function nick(&$irc, &$data)
	{
		global $nicks;
		if(in_array($data->nick, $nicks)) {
			if(isset($data->messageex[1])) {    
				$newnick = $data->messageex[1];
				$channel = $data->channel;  
				$irc->changeNick($newnick );
			}
		}      
	}

	/*Function that does the actual nickchange*/

	function changeNick($newnick, $priority = SMARTIRC_MEDIUM)
	{
		$this->_send('NICK '.$newnick, $priority);
		$this->_nick = $newnick;
	}
	
	function test_channel(&$irc, &$data)
	{
		$channel = $data->channel;
		$nick = $data->nick;
		$hostmask = $data->host;
		$message = $data->message;
	
		$command = substr($message, 1);
		$params = explode(" ", trim($command));
		$command = $params[0];
		unset($params[0]);
		$params = array_values($params);

		try
		{
			$fileName = "/storage/bot/scripts/irc_$command.php";
			echo $fileName;
			if(!file_exists($fileName))
			{
				$msg = "Unknown command: $command";
			}
			else
			{
				require_once $fileName;
				$className = "irc_$command";
				$class = new $className;
				if(!is_a($class, "ircCommand"))
				{
					$msg = "Module $command does not implement interface ircCommand!";
				}
				else
				{
					$accessLevel = Db::queryField("select accessLevel from zz_irc_access where name = :name and host = :host", "accessLevel",
							array(":name" => $nick, ":host" => $hostmask), 0);
					if ($accessLevel === null) $accessLevel = 0;
					if ($accessLevel < $class->getRequiredAccessLevel()) $msg = "You do not have access to the $command command.";
					$msg = $class->execute($nick, $hostmask, "pm", $command, $params, $accessLevel);
					echo $msg;
				}
			}
		}
		catch (Exception $ex)
		{
			$msg = "$command ended with error: ".$ex->getMessage();
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL, $channel, addIRCColors($msg));
	}
	
	function test_query(&$irc, &$data)
	{
		$nick = $data->nick;
		$hostmask = $data->host;
		$message = $data->message;
	
		$command = substr($message, 1);
		$params = explode(" ", trim($command));
		$command = $params[0];
		unset($params[0]);
		$params = array_values($params);

		try
		{
			$fileName = "/storage/bot/scripts/irc_$command.php";
			echo $fileName;
			if(!file_exists($fileName))
			{
				$msg = "Unknown command: $command";
			}
			else
			{
				require_once $fileName;
				$className = "irc_$command";
				$class = new $className;
				if(!is_a($class, "ircCommand"))
				{
					$msg = "Module $command does not implement interface ircCommand!";
				}
				else
				{
					$accessLevel = Db::queryField("select accessLevel from zz_irc_access where name = :name and host = :host", "accessLevel",
							array(":name" => $nick, ":host" => $hostmask), 0);
					if ($accessLevel === null) $accessLevel = 0;
					if ($accessLevel < $class->getRequiredAccessLevel()) $msg = "You do not have access to the $command command.";
					$msg = $class->execute($nick, $hostmask, "pm", $command, $params, $accessLevel);
					echo $msg;
				}
			}
		}
		catch (Exception $ex)
		{
			$msg = "$command ended with error: ".$ex->getMessage();
		}
		$irc->message(SMARTIRC_TYPE_QUERY, $nick, addIRCColors($msg));
	}
	
/*End the Bot-class*/
}

$colors = array(
	"|r|" => "\x034 ", // red
	"|g|" => "\x033 ", // green
	"|w|" => "\x031 ", // white
	"|b|" => "\x032 ", // blue
	"|blk|" => "\x030 ", // black
	"|c|" => "\x0311 ", // cyan
	"|y|" => "\x038 ", // yellow
	"|n|" => "\x03 ", // reset
);

function addIRCColors($msg)
{
	global $colors;
	foreach ($colors as $color => $value) {
		$msg = str_replace($color, $value, $msg);
	}
	return $msg;
}

function stripIRCColors($msg)
{
	global $colors;
	foreach ($colors as $color => $value) {
		$msg = str_replace($color, "", $msg);
	}
	return $msg;
}
	
interface ircCommand
{
	public function getRequiredAccessLevel();
	public function getDescription();
	public function execute($nick, $uhost, $channel, $command, $parameters, $nickAccessLevel);
	public function isHidden();
}
/*Start the bot and set some settings*/

$bot = &new mybot();
$irc = &new Net_SmartIRC();
$irc->setDebug(SMARTIRC_DEBUG_ALL);
$irc->setUseSockets(TRUE);

/*Bind IRC Commands to the above defined functions and end the PHP-file*/

$irc->connect('irc.coldfront.net', 6667);
// The actual zKB stuff
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^\.(.+?)$', $bot, 'test_query');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^\.(.+?)$', $bot, 'test_channel');

$irc->registerActionhandler(SMARTIRC_TYPE_KICK, '.*', $bot, 'kick_response');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '!restart', $bot, 'quit');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!kick', $bot, 'kick');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!voice', $bot, 'voice');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!devoice', $bot, 'devoice');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!op', $bot, 'op');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!deop', $bot, 'deop');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!join', $bot, 'join');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!part', $bot, 'part');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!rejoin', $bot, 'rejoin');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!ban', $bot, 'ban');
$irc->registerActionhandler(SMARTIRC_TYPE_CHANNEL, '^!nick', $bot, 'nick');

// nick , nome , realname , ident, senha do nick
$irc->login($nick, $nick, $nick, 8, $nick, '');
$irc->join($channels);
$irc->listen();
$irc->disconnect();