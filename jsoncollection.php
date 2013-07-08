<?php

include_once "jsonobject.php";

class JSONCollection extends ArrayObject implements JSONObject
{
	public function __construct($data=array())
	{
		if(!is_array($data) && !$data instanceof ArrayObject)
			return NULL;
		
		parent::__construct($data);
	}
	
	private function convertToArray($data, $case = "camel")
	{
		$array = array();
		foreach($data as $key => $value)
		{
			if(is_array($value) || $value instanceof ArrayObject)
				$array[$key] = $this->convertToArray($value, $case);
			else if($value instanceof JSONObject)
				$array[$key] = $value->toArray($case);
			else
				$array[$key] = $value;
		}
		return $array;
	}
	
	public function toArray($case = "camel")
	{
		return $this->convertToArray($this, $case);
	}
	
	public function toJSON($case = "camel")
	{
		return json_encode($this->toArray($case), JSON_HEX_AMP|JSON_HEX_QUOT);
	}
}



?>