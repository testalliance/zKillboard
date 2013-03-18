<?php
$sender = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ);
$sender->connect("tcp://127.0.0.1:3999");
$sender->setSockOpt(ZMQ::SOCKOPT_LINGER, 1000);

$sender->send($mail);