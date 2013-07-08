<?php

include_once "nameformatter.php";

class FileParams extends ArrayObject
{
	public function __construct($files = false)
	{
		if(!$files)
			$files = $_FILES;
			
		foreach($files as $key => $file)
		{
			if($file["error"]) continue;
			
			$value = (object) array
			(
				"originalName" => $file["name"],
				"original_name" => $file["name"],
				"temporaryName" => $file["tmp_name"],
				"temporary_name" => $file["tmp_name"],
				"type" => $file["type"],
				"size" => $file["size"],
			);
			
			$this[$key] = $value;
			if(NameFormatter::isSnakeCase($key))
				$this[NameFormatter::toCamelCase($key)] = $value;
			else if(NameFormatter::isCamelCase($key))
				$this[NameFormatter::toSnakeCase($key)] = $value;
		}
	}
	
	public function __get($key)
	{
		return isset($this[$key]) ? $this[$key] : NULL;
	}
}

?>