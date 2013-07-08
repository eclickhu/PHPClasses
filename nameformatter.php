<?php

class NameFormatter
{
	public static function isSnakeCase($key)
	{
		return preg_match("/^[a-z]+(_[a-z]+)*$/", $key);
	}
	
	public static function isCamelCase($key)
	{
		return preg_match("/^[a-z]+([A-Z][a-z]+)*$/", $key);
	}
	
	public static function toCamelCase($key) 
	{  
		return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));  
	}
	
	public static function toSnakeCase($key) {  
		return preg_replace_callback('/[A-Z]/', create_function('$match', 'return "_" . strtolower($match[0]);'), $key);  
	}  

}
?>