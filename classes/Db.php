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
	/**
	 * @var int Stores the number of Query executions and inserts
	 */
	protected static $queryCount = 0;

	/**
	 * Creates and returns a PDO object.
	 *
	 * @static
	 * @return PDO
	 */
	protected static function getPDO()
	{
		global $dbUser, $dbPassword, $dbName, $dbHost, $dbSocket;

		if($dbSocket)
			$dsn = "mysql:dbname=$dbName;unix_socket=$dbSocket";
		else
			$dsn = "mysql:dbname=$dbName;host=$dbHost";

		try
		{
			$pdo = new PDO($dsn, $dbUser, $dbPassword, array(
				PDO::ATTR_PERSISTENT => true, // Keep the connection open, so it can be reused
				PDO::ATTR_EMULATE_PREPARES => true, // Use native prepares, since they and the execution plan is cached in MySQL, and thus generate faster queries, but more garbled errors if we make any.
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Used buffered queries
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Error mode
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone = \'+00:00\'' // Default to using UTC as timezone for all queries.. Since EVE is UTC, so should we be!
				)
			);
		}
		catch (Exception $e)
		{
			Log::log("Unable to connect to the database: " . $e->getMessage());
			$pdo = null;
		}

		return $pdo;
	}

	/**
	 * Executes an SQL query, returns the full result
	 *
	 * @static
	 * @param string $query The query to be executed.
	 * @param array $parameters (optional) A key/value array of parameters.
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return Returns the full resultset as an array.
	 */
	public static function query($query, $parameters = array(), $cacheTime = 30)
	{
		global $dbExplain;

		// Sanity check
		if(strpos($query, ";") !== false)
			throw new Exception("Semicolons are not allowed in queryes. Use parameters instead.");

		// Disallow update, insert etc. with this, they have to use execute
		$contain = array("UPDATE", "INSERT");
		if(Util::strposa($query, $contain))
			throw new Exception("You are not to use Db::query with update or insert queries. Use Db::execute for that");

		// Cache time of 0 seconds means skip all caches. and just do the query
		$key = self::getKey($query, $parameters);

		// If cache time is above 0 seconds, lets try and get it from that.
		if($cacheTime > 0)
		{
			// Try the cache system
			$result = Cache::get($key);
			if($result !== FALSE)
				return $result;
		}

		try
		{
			// Start the timer
			$timer = new Timer();
			// Increment the queryCounter
			self::$queryCount++;
			// Open the databse connection
			$pdo = self::getPDO();
			// Make sure PDO is set
			if($pdo == NULL)
				return;
			// Prepare the query
			$stmt = $pdo->prepare($query);
			// Execute the query, with the parameters
			$stmt->execute($parameters);

			// Check for errors
			if($stmt->errorCode() != 0)
				self::processError($stmt, $query, $parameters);

			// Fetch an associative array
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			// Close the cursor
			$stmt->closeCursor();
			// Close the PDO object
			$pdo = null;
			// Stop the timer
			$duration = $timer->stop();

			// If cache time is above 0 seconds, lets store it in the cache.
			if($cacheTime > 0)
				Cache::set($key, $result, min(3600, $cacheTime)); // Store it in the cache system

			// If dbExplain is enabled, we need to pass the query to the explain system
			if($dbExplain && !strpos($query, "explain"))
				if(stripos($query, "SELECT") !== FALSE)
					self::explainQuery($query, $parameters, $duration);

			// If the duration of the query was more than 5000 seconds, we need to log it..
			if($duration > 5000)
				self::log($query, $parameters, $duration);

			// now to return the result
			return $result;
		}
		catch (Exception $e)
		{
			// There was some sort of nasty nasty nasty error..
			throw $e;
		}
	}

	/**
	 * Executes an SQL query, and returns a single row
	 *
	 * @static
	 * @param string $query The query to be executed
	 * @param array $parameters (optional) A key/value array of parameters
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return Returns the first row of the result set. Returns null if there are no rows.
	 */
	public static function queryRow($query, $parameters = array(), $cacheTime = 30)
	{
		// Get the result
		$result = self::query($query, $parameters, $cacheTime);
		// Figure out if it has more than one result and return it
		if(sizeof($result) >= 1)
			return $result[0];

		// No results at all
		return null;
	}

	/**
	 * Executes an SQL query, and returns a single result
	 *
	 * @static
	 * @param string $query The query to be executed
	 * @param string $field The name of the field to return
	 * @param array $parameters (optional) A key/value array of parameters
	 * @param int $cacheTime The time, in seconds, to cache the result of the query.	Default: 30
	 * @return null Returns the value of $field in the first row of the resultset. Returns null if there are no rows.
	 */
	public static function queryField($query, $field, $parameters = array(), $cacheTime = 30)
	{
		// Get the result
		$result = self::query($query, $parameters, $cacheTime);
		// Figure out if it has no results
		if(sizeof($result) == 0)
			return null;

		// Bind the first result to $resultRow
		$resultRow = $result[0];

		// Return the result + the field requested
		return $resultRow[$field];
	}

	/**
	 * Executes an SQL command and returns the number of rows affected.
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
		// Sanity check
		if(strpos($query, ";") !== false)
			throw new Exception("Semicolons are not allowed in queryes. Use parameters instead.");

		// Start the timer
		$timer = new Timer();
		// Increment the queryCounter
		self::$queryCount++;
		// Open the databse connection
		$pdo = self::getPDO();
		// Make sure PDO is actually set
		if($pdo == NULL)
			return;
		// Begin the transaction
		$pdo->beginTransaction();
		// Prepare the query
		$stmt = $pdo->prepare($query);
		// Execute the query, with the parameters
		$stmt->execute($parameters);

		// An error happened
		if($stmt->errorCode() != 0)
		{
			// Report the error
			if($reportErrors) self::processError($stmt, $query, $parameters);
			// Rollback the query
			$pdo->rollBack();
			// Return false
			return $false;
		}

		// No error, time to commit
		$pdo->commit();
		// Stop the timer
		$duration = $timer->stop();
		// If the duration of the query was more than 5000 seconds, we need to log it..
		if($duration > 5000)
			self::log($query, $parameters, $duration);

		// Get the amount of rows that was altered
		$rowCount = $stmt->rowCount();
		// Close the cursor
		$stmt->closeCursor();
		// Unset the PDO object
		$pdo = null;

		// Return the amount of rows that was altered
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
		return self::$queryCount;
	}

	/**
	 * @static
	 * @throws Exception
	 * @param	PDOStatement $statement
	 * @param	string $query
	 * @param	array $parameters
	 * @return void
	 */
	public static function processError($statement, $query, $parameters = array())
	{
		$errorCode = $statement->errorCode();
		$errorInfo = $statement->errorInfo();
		self::log("$errorCode - " . $errorInfo[2] . "\n$query", $parameters);
		throw new Exception($errorInfo[0] . " - " . $errorInfo[1] . " - " . $errorInfo[2]);
	}

	/**
	 * Takes a query, and explains it, and drops it into a db table for later perusal..
	 *
	 * @param string $query
	 * @return void
	 */
	public static function explainQuery($query, $parameters = array(), $duration = 0)
	{
		$query = "explain ". $query;
		$hash = md5($query);
		$find = array();
		$replace = array();
		foreach($param as $key => $value)
		{
			$find[] = $key;
			$replace[] = "'".$value."'";
		}
		$query = str_replace($find, $replace, $query);
		$pdo = self::getPDO();
		$stmt = $pdo->prepare($query);
		$stmt->execute();
		$explainResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		// insert it to a db..
		$insertQuery = "INSERT IGNORE INTO zz_query_stats VALUES (:hash, :query, :params, :selectType, :table, :queryType, :possibleKeys, :keyUsed, :keyLength, :ref, :rows, :extra, :duration)";
		$parameters = array(
				":hash" => $hash,
				":query" => str_replace("explain", "", $query),
				":params" => implode(", ", $param),
				":selectType" => $explainResult[0]["select_type"],
				":table" => $explainResult[0]["table"],
				":queryType" => $explainResult[0]["type"],
				":possibleKeys" => $explainResult[0]["possible_keys"],
				":keyUsed" => $explainResult[0]["key"],
				":keyLength" => $explainResult[0]["key_len"],
				":ref" => $explainResult[0]["ref"],
				":rows" => $explainResult[0]["rows"],
				":extra" => $explainResult[0]["Extra"],
				":duration" => $duration
			);

		$pdo = self::getPDO();
		$stmt = $pdo->prepare($insertQuery);
		$stmt->execute($parameters);
		$stmt->closeCursor();
		$pdo = null;
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
	 * @param string $query The query.
	 * @param array $parameters The parameters
	 * @return string The query and parameters as a hashed value.
	 */
	public static function getKey($query, $parameters = array())
	{
		foreach($parameters as $key => $value)
			$query .= "|$key|$value";

		return "Db:" . md5($query);
	}
}