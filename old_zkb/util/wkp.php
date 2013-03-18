<?php

$count = 0;
echo "PARTITION BY RANGE COLUMNS (YEAR, WEEK) (";
for ($y = 2007; $y <= 2014; $y++) for ($w = 2; $w <= 53; $w+=4) {
	$ww = $w - 1;
	echo "PARTITION p{$y}_$ww VALUES LESS THAN ($y, $w), ";
}
echo "PARTITION pmax VALUES LESS THAN (MAXVALUE, MAXVALUE));\n";
