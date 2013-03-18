<?php
class zMQ
{
	public static function sendMessage($mail)
	{
		$sender = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ); // sets up the zmq socket
		$sender->connect("tcp://127.0.0.1:3999"); // connects to the zmq server
		$sender->setSockOpt(ZMQ::SOCKOPT_LINGER, 10); // sets the socket to persist for a bit
		$mail = gzcompress(json_encode($mail), 9); // compress it hard yo!
		$sender->send($mail); // sends the mail
	}
}