<?php

include_once "sql.php";
include_once "nameformatter.php";
include_once "jsonobject.php";

abstract class Model implements JSONObject
{
	protected static $TABLE_INFO = array();
	protected $_sql;
	protected $_tableName;
	protected $_primaryKeyName;
	protected $_primaryKeyProperty;
	
	public function __construct($data = NULL)
	{
		$this->_sql = Sql::getInstance();
		self::_loadColumnInfo($this->_tableName);
		$this->_buildPrimaryKey();
		$this->_load($data);
	}
	
	public function isValid()
	{ 
		return true; 
	}

	public function save()
	{
		$validity = $this->isValid();
		if($validity !== true)
			return false;
		
		$reqColumns = $this->_buildRequiredColumns();
		$reqColumnNames = array_keys($reqColumns);
		$reqColumnValues = array_values($reqColumns);
		
		$reqColumnNamesStr = implode(",", $reqColumnNames);
		$reqQuestionMarks = implode(",", array_fill(0, count($reqColumnValues), "?"));
		
		$reqColumnUpdates = array();
		foreach ($reqColumnNames as $col)
			$reqColumnUpdates[] = $col."=VALUES(".$col.")";
		$reqColumnUpdates = implode(",", $reqColumnUpdates);
		
		$query = "INSERT INTO {$this->_tableName} 
			({$reqColumnNamesStr}) 
		VALUES 
			({$reqQuestionMarks}) 
		ON DUPLICATE KEY 
		UPDATE 
			{$reqColumnUpdates}";
			
		if($this->_sql->query($query, $reqColumnValues) === false)
			return false;
		
		$pkProp = $this->_primaryKeyProperty;
		if($this->$pkProp)
			return true;
		
		$query = "SELECT MAX({$this->_primaryKeyName}) as last_id FROM {$this->_tableName}";
		$result = $this->_sql->query($query);
		if($result === false || count($result) === 0)
			return false;
			
		$this->$pkProp = $result[0]["last_id"];
		return true;
	}
	
	public function delete()
	{
		$pkName = $this->_primaryKeyName;
		$pkProperty = $this->_primaryKeyProperty;
		$pkValue = $this->$pkProperty;
		$query = "DELETE FROM {$this->_tableName} WHERE {$pkName}=?";
		return $this->_sql->execute($query, array($pkValue)) === false ? false : true;
	}
	
	public function sqlError()
	{
		return $this->_sql->error;	
	}
	
	public function toArray($case = "camel")
	{
		$properties = get_object_vars($this);
		$array = array();
		
		foreach($properties as $key => $value)
		{
			if($key[0] == "_") continue;

			switch($case)
			{
				case "snake":
					$array[NameFormatter::isSnakeCase($key) ? $key : NameFormatter::toSnakeCase($key)] = $value;
				break;
				case "mixed":
					$array[NameFormatter::isSnakeCase($key) ? $key : NameFormatter::toSnakeCase($key)] = $value;
					$array[NameFormatter::isCamelCase($key) ? $key : NameFormatter::toCamelCase($key)] = $value;
				break;
				default:
					$array[NameFormatter::isCamelCase($key) ? $key : NameFormatter::toCamelCase($key)] = $value;
				break;
			}
		}

		return $array;
	}
	
	public function toJSON($case = "camel")
	{
		return json_encode($this->toArray($case), JSON_HEX_AMP|JSON_HEX_QUOT);
	}
	
	private static function _arrayFrom(&$array, $Class)
	{
		$items = array();
		foreach($array as $row)
			$items[] = new $Class($row);
		return $items;
	}
	
	public static function all()
	{
		$ChildClass = self::_childClass();
		$childTableName = self::_childTableName();
		$sql = Sql::getInstance();
		$query = "SELECT * FROM {$childTableName}";
		$result = $sql->query($query);
		if($result === false)
			return false;
		return self::_arrayFrom($result, $ChildClass);
	}
	
	public static function range($from = 0, $limit = 10)
	{
		$ChildClass = self::_childClass();
		$childTableName = self::_childTableName();
		$sql = Sql::getInstance();
		$query = "SELECT * FROM {$childTableName} LIMIT {$from}, $limit";
		$result = $sql->query($query);
		if($result === false)
			return false;
		return self::_arrayFrom($result, $ChildClass);
	}
	
	protected static function _childClass()
	{
		return get_called_class();
	}
	
	protected static function _childTableName()
	{
		$ChildClass = self::_childClass();
		$childObj = new $ChildClass();
		return $childObj->_tableName;
	}
	
	protected function _load($input)
	{
		if(!$input) return;
		if(is_array($input) || $input instanceof ArrayObject)
			$this->_loadDirty($input);
		else
			$this->_loadById($input);
	}
	
	protected function _loadById($id)
	{
		$query = "SELECT * FROM {$this->_tableName} WHERE {$this->_primaryKeyName} = ?";
		$result = $this->_sql->query($query, array($id));
		if($result === false)
			return false;
		if(count($result)==0)
			return false;	
		$this->_loadDirty($result[0]);
	}
	
	protected function _loadDirty($data)
	{
		if(!is_array($data) && !($data instanceof ArrayObject))
			return false;
	
		foreach($data as $key => $value)
		{
			$ccKey = NameFormatter::isCamelCase($key) ? $key : NameFormatter::toCamelCase($key);
			$usKey = NameFormatter::isSnakeCase($key) ? $key : NameFormatter::toSnakeCase($key);

			if(property_exists($this, $key))
				$this->$key = $value;
			else if(property_exists($this, $ccKey))
				$this->$ccKey = $value;
			else if(property_exists($this, $usKey))
				$this->$usKey = $value;
		}
	}
	
	protected function _buildPrimaryKey()
	{
		$columns = &self::$TABLE_INFO[$this->_tableName];
		foreach($columns as $name => $column)
			if($column->isPrimaryKey)
				$this->_primaryKeyName = $name;
		
		$pkName = $this->_primaryKeyName;
		$pkCcName = NameFormatter::isCamelCase($pkName) ? $pkName : NameFormatter::toCamelCase($pkName);
		$pkUsName = NameFormatter::isSnakeCase($pkName) ? $pkName : NameFormatter::toSnakeCase($pkName);
		
		if(property_exists($this, $pkName))
			$this->_primaryKeyProperty = $pkName;
		else if(property_exists($this, $pkCcName))
			$this->_primaryKeyProperty = $pkCcName;
		else if(property_exists($this, $pkUsName))
			$this->_primaryKeyProperty = $pkUsName;
	}

	protected function _buildRequiredColumns()
	{
		$columns = array();
		$sqlColumns = self::$TABLE_INFO[$this->_tableName];
		$propTable = get_object_vars($this);
		
		foreach($sqlColumns as $key => $value)
		{
			$usKey = NameFormatter::isSnakeCase($key) ? $key : NameFormatter::toSnakeCase($key);
			$ccKey = NameFormatter::isCamelCase($key) ? $key : NameFormatter::toCamelCase($key);
			
			if(array_key_exists($key, $propTable))
				$columns[$key] = $propTable[$key];
			else if(array_key_exists($ccKey, $propTable))
				$columns[$key] = $propTable[$ccKey];
			else if(array_key_exists($usKey,$propTable))
				$columns[$key] = $propTable[$usKey];
		}
		
		return $columns;
	}
	
	private static function _loadColumnInfo($table)
	{
		if(isset(self::$TABLE_INFO[$table])) 
			return true;
			
		$result = Sql::getInstance()->query("SHOW COLUMNS FROM {$table}");
		if($result === false)
			return false; 
			
		$columns = array();
		
		foreach($result as $row)
		{
			$info = array
			(
				"isPrimaryKey" => $row["Key"] === "PRI",
				"isNullable" => $row["Null"] === "YES",
				"length" => preg_replace('/[^0-9,]|,[0-9]*$/','',$row["Type"]),
				"type" => strtoupper(preg_replace('/[^0-9]/s', '', $row["Type"])),
				"default" => $row["Default"],
			);
			
			$columns[$row["Field"]] = (object) $info;
		}
		
		self::$TABLE_INFO[$table] = $columns;
		return true;
	}
}

?>