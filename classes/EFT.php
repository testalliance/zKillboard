<?php
class EFT
{
	public static function getText($array)
	{
		$eft = "";
		$item = "";
		if (isset($array["low"])) foreach ($array["low"] as $flags)
		{
			foreach ($flags as $items)
			{
				$item = $items["typeName"] . "\n";
			}
			$eft .= $item;
		}
		$eft .= "\n";
		$item = "";
		if (isset($array["mid"])) foreach ($array["mid"] as $flags)
		{
			$cnt = 0;
			foreach ($flags as $items)
			{
				if ($cnt == 0)
					$item = $items["typeName"];
				else
					$item .= "," . $items["typeName"];
				$cnt++;
			}
			$item .= "\n";
			$eft .= $item;
		}
		$eft .= "\n";
		$item = "";
		if (isset($array["high"])) foreach ($array["high"] as $flags)
		{
			$cnt = 0;
			foreach ($flags as $items)
			{
				if ($cnt == 0)
					$item = $items["typeName"];
				else
					$item .= "," . $items["typeName"];
				$cnt++;
			}
			$item .= "\n";
			$eft .= $item;
		}
		$eft .= "\n";
		$item = "";
		if (isset($array["rig"])) foreach ($array["rig"] as $flags)
		{
			foreach ($flags as $items)
			{
				$item = $items["typeName"] . "\n";
			}
			$eft .= $item;
		}
		$eft .= "\n";
		$item = "";
		if (isset($array["sub"])) foreach ($array["sub"] as $flags)
		{
			foreach ($flags as $items)
			{
				$item = $items["typeName"] . "\n";
			}
			$eft .= $item;
		}
		$eft .= "\n";
		$item = "";
		if (isset($array["drone"])) foreach ($array["drone"] as $flags)
		{
			foreach ($flags as $items)
			{
				$item .= $items["typeName"] . " x" . $items["qty"] . "\n";
			}
			$eft .= $item;
		}
		return trim($eft);
	}
}
