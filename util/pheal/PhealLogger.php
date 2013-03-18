<?php

class PhealLogger
{
	private $logID = null;

    public function start() {}

    public function stop() {}

    public function log($scope,$name,$opts) {
		Db::execute("insert delayed into zz_api_log (scope, name, options) values (:scope, :name, :options)",
				array(":scope" => $scope, ":name" => $name, ":options" => json_encode($opts)));
	}

    public function errorLog($scope,$name,$opts,$message) {
		Db::execute("insert delayed into zz_api_log (scope, name, options, errorCode) values (:scope, :name, :options, :error)",
				array(":scope" => $scope, ":name" => $name, ":options" => json_encode($opts), ":error" => substr($message, 0, 127)));
	}
}
