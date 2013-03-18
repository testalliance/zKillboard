#!/bin/bash

cd /var/killboard/zkillboard.com/util
php5 processRawApi.php $(ls /var/log/api_killlogs/ | tail -n 10000)
