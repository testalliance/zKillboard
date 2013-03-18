<?php

class Storage
{
	public static function retrieve($locker, $default = null)
	{
		$contents = Db::queryField("select contents from zz_storage where locker = :locker", "contents", array(":locker" => $locker), 1);
		if ($contents === null) return $default;
		return $contents;
	}

	public static function store($locker, $contents)
	{
		return Db::execute("replace into zz_storage (locker, contents) values (:locker, :contents)", array(":locker" => $locker, ":contents" => $contents));
	}
}
