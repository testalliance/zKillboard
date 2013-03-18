<?php

$count = 0;
echo "PARTITION BY RANGE COLUMNS (killTime) (";
for ($y = 2007; $y <= 2014; $y++) for ($m = 2; $m <= 13; $m++) {
	$month = $m;
	$year = $y;
	if ($month == 13) { $year++; $month = 1;}
	$sm = strlen("$m") < 2 ? "0$m" : $m;
	$month--;
	$month = strlen("$month") < 2 ? "0$month" : $month;
	echo "PARTITION p{$year}_$month VALUES LESS THAN ('$y-$sm-01'), ";
}
echo "PARTITION pmax VALUES LESS THAN (MAXVALUE));\n";
