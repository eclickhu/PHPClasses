<?php

include_once 'config.php';

class Sql 
{
	public $error;
	private $connection;
	private $connected = false;
	private static $DEFAULTS = array
	(
		"host" => "localhost", 
		"db" => "test",
		"user" => "root", 
		"pass" => "", 
		"driver" => "mysql"
	);
	
	public function __construct($data = array()) 
	{
		$data = array_merge(self::$DEFAULTS, $data);

		$dsn = $data["driver"] . ":host=" . $data["host"] . ";dbname=". $data["db"];
		$user = $data["user"];
		$pass = $data["pass"];
		
		$settings = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'");
		
		try 
		{
			$this->connection = new PDO($dsn, $user, $pass, $settings);
			$this->connected = true;
			
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch(PDOException $ex)
		{
			$this->error = $ex->getMessage();
		}
	}

	public function query($query, $bindParams = NULL) 
	{
		if(!$this->connected)
			return false;

		$this->error = '';
		
		if($bindParams == NULL)
		{
			try 
			{
				$statement = $this->connection->query($query);
				$result = array();
				foreach ($statement as $row)
					array_push($result, $row);
				return $result;
			}
			catch (PDOException $ex)
			{
				$this->error = $ex->getMessage();
				return false;
			}
		}
		else
		{
			return $this->executePrepared($query, $bindParams);
		}
	}
	
	public function execute($query, $bindParams = NULL)
	{
		if(!$this->connected)
			return false;
		
		$this->error = '';
		if($bindParams == NULL)
			return $this->executeNonPrepared($query);
		else
			return $this->executePrepared($query, $bindParams);
	}
	
	private function executeNonPrepared($query)
	{
		try 
		{
			return $this->connection->exec($query);
		}
		catch(PDOException $ex)
		{
			$this->error = $ex->getMessage();
			return false;
		}
	}
	
	
	private function executePrepared($query, $bindParams)
	{
		try 
		{
			$statement = $this->connection->prepare($query);
			$success = $statement->execute($bindParams);
			
			if(!strncmp(strtoupper($query), "SELECT", strlen("SELECT")))
				$result = $statement->fetchAll(PDO::FETCH_ASSOC);
			else $result = $success;
			
			return $result;
		}
		catch(Exception $ex)
		{
			$this->error = $ex->getMessage();
			return false;
		}
	}
	
	static public function getInstance() 
	{
		static $instance = NULL;
		if(!$instance)
		{
			/*
			!!! Provide valid connection data here !!!
			*/
			$data = self::$DEFAULTS;
			$instance = new Sql($data);
		}
		return $instance;
	}
	
	public function __destruct() 
	{
		unset($this->connection);
		$this->connection = NULL;
	}
}
?>