<?php

interface JSONObject
{
	public function toArray($case = "camel");
	public function toJSON($case = "camel");
}

?>