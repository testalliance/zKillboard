<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Db
{
	function __destruct()
	{
		global $pdo;
		$pdo = null;
	}

	/**
	 * @var int Stores the number of Query executions and inserts
	 */
	protected static $queryCount = 0;

	/**
	 * @static
	 * @param string $query The query.
	 * @param array $parameters The parameters
	 * @return string The query and parameters as a hashed value.
	 */
	public static function getKey($query, $parameters)
	{
		$key = $query;
		foreach ($parameters as $k => $v) {
			$key .= "|$k|$v";
		}
		return "Db:" . md5($key);
	}

	/**
	 * Creates and returns a PDO object.
	 *
	 * @static
	 * @return PDO
	 */
	protected static function getPDO()
	{
		global $dbUser, $dbPassword, $dbName, $dbHost, $pdo;

		if (isset($pdo)) return $pdo;

		$dsn = "mysql:dbname=$dbName;host=$dbHost";

		try {
			$pdo = new PDO($dsn, $dbUser, $dbPassword, array(PDO::ATTR_PERSISTENT => true));
			//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (Exception $ex) {
			Log::log("Unable to connect to database: " . $ex->getMessage());
			throw $ex;
		}
		Db::execute("rollback");
		Db::execute("set session wait_timeout = 30");
		Db::$queryCount = 0;
		return $pdo;
	}

	/**
	 * Logs a query, its parameters, and the amount of time it took to execute.
	 * The original query is modified through simple search and replace to create
	 * the query as close to the execution as PDO would have the query.	This
	 * logging function doesn't take any care to escape any parameters, so take
	 * caution if you attempt to execute any logged queries.
	 *
	 * @param string $query The query.
	 * @param array $parameters A key/value array of parameters
	 * @param int $duration The length of time it took for the query to execute.
	 * @return void
	 */
	public static function log($query, $parameters = array(), $duration = 0)
	{
		global $baseAddr;
		foreach ($parameters as $k => $v) {
			$query = str_replace($k, "'" . $v . "'", $query);
		}
		$uri = isset($_SERVER["REQUEST_URI"]) ? "Query page: https://$baseAddr" . $_SERVER["REQUEST_URI"] . "\n": "";
		Log::log(($duration != 0 ? number_format($duration / 1000, 3) . "s " : "") . " Query: \n$query;\n$uri");
	}

	/**
	 * @static
	 * @throws Exception
	 * @param	$statement
	 * @param	string $query
	 * @param	array $parameters
	 * @return void
	 */
	private static function processError($statement, $query, $parameters)
	{
		$errorCode = $statement->errorCode();
		$errorInfo = $statement->errorInfo();
		Db::log("$errorCode - " . $errorInfo[2] . "\n$query", $parameters);
		throw new Exception($errorInfo[0] . " - " . $errorInfo[1] . " - " . $errorInfo[2]);
	}

	private static $horribleQueryMutexArray = array();

	/**
	 * @static
	 * @param string $query The query to be executed.
	 * @param array $params (optional) A key/value array of parameters.
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return Returns the full resultset as an array.
	 */
	public static function query($query, $params = array(), $cacheTime = 30)
	{
		// Basic sanity check.
		if (strpos($query, ";") !== false) throw new Exception("Semicolons are not allowed in queries.  Use parameters instead.");

		// cacheTime of 0 or less means skip all caches and just do the query
		$key = Db::getKey($query, $params);
		// Horrible but temporary mutex solution to ensure a single version of a
		// query is being executed once, and only once at a given moment
		// yes , this is perfect, atm I don't care -- Squizz
		// (karbowiak made me do it)
		// TODO implement this properly
		while (array_key_exists($key, self::$horribleQueryMutexArray)) sleep(100);

		if ($cacheTime > 0) {

			// First, check our local storage bin
			$result = Bin::get($key, FALSE);
			if ($result !== FALSE) return $result;

			// Second, check MemCache
			$result = Cache::get($key);
			if ($result !== FALSE) return $result;
		}

		self::$horribleQueryMutexArray[$key] = true;
		
		try {
			Db::$queryCount++;

			// OK, hit up the database, but let's time it too
			$timer = new Timer();
			$pdo = Db::getPDO();
			$stmt = $pdo->prepare($query);
			$stmt->execute($params);
			if ($stmt->errorCode() != 0) Db::processError($stmt, $query, $params);

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			$duration = $timer->stop();

			if ($cacheTime > 0) {
				Bin::set($key, $result);
				Cache::set($key, $result, min(3600, $cacheTime));
			}
			if ($duration > 5000) Db::log($query, $params, $duration);

			unset(self::$horribleQueryMutexArray[$key]);
			return $result;
		} catch (Exception $ex) {
			unset(self::$horribleQueryMutexArray[$key]);
			throw $ex;

		}
	}

	/**
	 * @static
	 * @param string $query The query to be executed
	 * @param array $parameters (optional) A key/value array of parameters
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return Returns the first row of the result set. Returns null if there are no rows.
	 */
	public static function queryRow($query, $parameters = array(), $cacheTime = 30)
	{
		$result = Db::query($query, $parameters, $cacheTime);
		if (sizeof($result) >= 1) return $result[0];
		return null;
	}

	/**
	 * @static
	 * @param string $query The query to be executed
	 * @param string $field The name of the field to return
	 * @param array $parameters (optional) A key/value array of parameters
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return null Returns the value of $field in the first row of the resultset. Returns null if there are no rows.
	 */
	public static function queryField($query, $field, $parameters = array(), $cacheTime = 30)
	{
		$result = Db::query($query, $parameters, $cacheTime);
		if (sizeof($result) == 0) return null;
		$resultRow = $result[0];
		return $resultRow[$field];
	}


	/**
	 * Executes a SQL command and returns the number of rows affected.
	 * Good for inserts, updates, deletes, etc.
	 *
	 * @static
	 * @param string $query The query to be executed.
	 * @param array $parameters (optional) A key/value array of parameters.
	 * @param boolean $reportErrors Log the query and throw an exception if the query fails. Default: true
	 * @return int The number of rows affected by the sql query.
	 */
	public static function execute($query, $parameters = array(), $reportErrors = true)
	{
		// Basic sanity check.
		if (strpos($query, ";") !== false) throw new Exception("Semicolons are not allowed in queries.  Use parameters instead.");

		$timer = new Timer();
		Db::$queryCount++;
		$pdo = Db::getPDO();
		$statement = $pdo->prepare($query);
		$statement->execute($parameters);

		if ($statement->errorCode() != 0) {
			if ($reportErrors) Db::processError($statement, $query, $parameters);
			return FALSE;
		}
		$duration = $timer->stop();
		if ($duration > 5000) Db::log($query, $parameters, $duration);

		$rowCount = $statement->rowCount();
		$statement->closeCursor();
		return $rowCount;
	}

	/**
	 * Retrieve the number of queries executed so far.
	 *
	 * @static
	 * @return int Number of queries executed so far
	 */
	public static function getQueryCount()
	{
		return Db::$queryCount;
	}
}
