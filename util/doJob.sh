#!/bin/bash

cd /var/killboard/zkillboard.com/util/

mkdir /tmp/locks/ 2>/dev/null

lockFile=/tmp/locks/$1.lock

flock -w 63 $lockFile php5 doJob.php $1
