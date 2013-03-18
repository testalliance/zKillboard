<?php
$context = new ZMQContext();
$subscriber = $context->getSocket(ZMQ::SOCKET_SUB);

// Connect to the first publicly available relay.
$subscriber->connect("tcp://82.221.99.219:4000");
// Disable filtering.
$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "");

$count = 0;
while (true) {
    $recv = $subscriber->recv();
    if($recv == NULL) // this makes no sense, but if it's != NULL, it only returns the null..
    {
        $killmail = gzuncompress($subscriber->recv());
        $killmail = json_decode($killmail);
        // Do stuff with the mail here
        echo $killmail->killID."\n";
        $count++;
        echo $count."\n";
    }
}