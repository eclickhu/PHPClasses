<?php

include_once "nameformatter.php";

class HttpParams extends ArrayObject
{
	public function __construct($params = array())
	{
		parent::__construct(array());
		$this->addAll($params);		
	}
	
	public function addAll($params)
	{
		foreach($params as $key => $value)
			$this->$key = $value;
	}

	public function __set($key, $value)
	{
		$this[$key] = $value;
		if(NameFormatter::isSnakeCase($key))
			$this[NameFormatter::toCamelCase($key)] = $value;
		else if(NameFormatter::isCamelCase($key))
			$this[NameFormatter::toSnakeCase($key)] = $value;
	}
	
	public function __get($key)
	{
		return isset($this[$key]) ? $this[$key] : NULL;
	}
}

?>