#!/usr/bin/env php
<?php

require_once("configuration.php");

//
// initialize JAXL object with initial config
//
require_once dirname(__FILE__) . '/jaxl/jaxl.php';
require_once dirname(__FILE__) . '/jaxl/core/jaxl_pipe.php';
require_once( "/storage/www/zkb/init.php" );

$pipe = new JAXLPipe("");

$client = new JAXL(array(
  // (required) credentials
  'jid' => USERNAME,
  'pass' => PASSWORD,
  'auth_type' => 'PLAIN',
  'host' => HOST,
  'port' => PORT,
  'force_tls' => FORCE_TLS,
  'resource' => RESOURCE,
  'strict' => false
));

$client->require_xep(array(
	'0045',	// MUC
	'0203',	// Delayed Delivery
    '0199', // XMPP Ping
));

//
// add necessary event callbacks here
//
$result = array();
foreach ($rooms as $room) {
  $_room_full_jid = $room . "@" . MUC_HOST . "/" . NICK;
  $result[$_room_full_jid] = new XMPPJid($_room_full_jid);
}
$rooms = $result;
unset($result);

$client->add_cb('on_auth_success', function() {
	global $client, $rooms;
	_debug("got on_auth_success cb, jid ".$client->full_jid->to_string());

	// join muc rooms
  foreach($rooms as $room => &$room_full_jid) {
    $client->xeps['0045']->join_room($room_full_jid);
  }
});

$client->add_cb('on_auth_failure', function($reason) {
	global $client;
	$client->send_end_stream();
	_debug("got on_auth_failure cb with reason $reason");
});

$client->add_cb('on_groupchat_message', function($stanza) {
	global $client;
	
	$from = new XMPPJid($stanza->from);
	$delay = $stanza->exists('delay', NS_DELAYED_DELIVERY);

	if($from->resource && !$delay && !preg_match('|/'.NICK.'|', $stanza->from)) {
    ah_chat_monitor($stanza, $from); 
	}
});

$client->add_cb('on_disconnect', function() {
	_debug("got on_disconnect cb");
});

$pipe->set_callback(function($data)
{
    global $pipe;
    global $client;
    $msg = new XMPPMsg(array('type'=>'groupchat', 'to'=>"asdfasdfasdf@conference.eve-mail.net", 'from'=>$client->full_jid->to_string()), $data);
    $client->send($msg);
});

function ah_chat_monitor($stanza, $from) {
  global $client;
  $base = dirname(__FILE__);
  $text = $stanza->body;

  if(substr($text, 0, 1) == ".")
  {
    $command = substr($text, 1);
    $params = explode(" ", trim($command));
    $command = $params[0];
    unset($params[0]);
    
    try
    {
        $fileName = "$base/scripts/$command.php";
        if(!file_exists($fileName))
        {
            $msg = "Unknown command: $command";
            $msg = new XMPPMsg(array('type'=>'groupchat', 'to'=>$from->bare, 'from'=>$client->full_jid->to_string()), $msg);
            $client->send($msg);
        }
        else
        {
            require_once $fileName;
            $className = "jab_$command";
            $class = new $className;
            if(!is_a($class, "jabCommand"))
            {
                $msg = "Unknown command: $command";
                $msg = new XMPPMsg(array('type'=>'groupchat', 'to'=>$from->bare, 'from'=>$client->full_jid->to_string()), $msg);
                $client->send($msg);
            }
            else
            {
                $msg = $class->execute($from->resource, $command, $params);
                $msg = new XMPPMsg(array('type'=>'groupchat', 'to'=>$from->bare, 'from'=>$client->full_jid->to_string()), $msg);
                $client->send($msg);
            }
        }
    }
    catch (Exception $ex)
    {
        $msg = "$command ended with error: ". $ex->getMessage();
        $msg = new XMPPMsg(array('type'=>'groupchat', 'to'=>$from->bare, 'from'=>$client->full_jid->to_string()), $msg);
        $client->send($msg);
    }
  }
}

interface jabCommand {
	public function getDescription();
	public function execute($nick, $command, $parameters);
}

//
// finally start configured xmpp stream
//

$client->start(array(
	'--with-unix-sock' => true
));
echo "done\n";