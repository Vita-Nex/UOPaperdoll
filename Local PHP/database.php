<?php
/*
 *	database.php
 *
 *	Ultima Online Paperdoll Image Generator
 *	Vorspire - http://core.vita-nex.com
 *
 *	License: MIT
 *	Created: 05/2011
 *	Updated: 01/2015
 */

$database = new MySQLDB();

class MySQLDB
{
    var $Connection;

	function MySQLDB()
	{
		$die = false;
		
	    if(!$this->Connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME))
		{
			die('We are currently having technical difficulties, please try again soon!');
			exit;
		}
   	}
   
	function query($query)
	{
		return mysqli_query($this->Connection, $query);
	}
   
	function fquery($query)
	{
		$result = mysqli_query($this->Connection, $query);

		$dbarray = mysqli_fetch_array($result);
		
		return $dbarray;
	}
   
	function mquery($query)
	{
		$result = mysqli_query($this->Connection, $query);

		for($i = 0; $row = mysqli_fetch_array($result); $i++)
		{
			$dbarray[$i] = $row;
		}

		return $dbarray;
	}

   	function GetTableList()
	{
		return $this->mquery("SHOW TABLES FROM " . DB_NAME);
   	}

	function Optimize($query)
	{
		$query = explode(" ", $query);
		$op = $query[0];

		if($op == "REPLACE")
			$table = $query[2];
		else
			$table = $query[1];

		$query = null;
	
		if($op == "DELETE" || $op == "UPDATE" || $op == "REPLACE")
		{
			$q = "OPTIMIZE TABLE '" . $table . "'";

			if($this->query($q))
				return true;
		}

		return false;
	}
}

?>