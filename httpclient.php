<?php
class HttpClient
{
	public $url;
	public $params = array();
	public $method = "GET";
	public $format = "TEXT";
	
	public $response = NULL;
	public $rawResponse = NULL;
	
	public $meta;
	
	public function __construct($url)
	{
		$this->url = $url;
	}
	
	public function send()
	{
		$method = strtoupper($this->method);
		$httpParams = array
		(
    		'http' => array
			(
      			'method' => $method,
      			'ignore_errors' => true
			)
    	);
		
		if ($this->params && is_array($this->params)) 
		{
			$paramsStr = http_build_query($this->params);
			if ($method == 'POST') 
			  	$httpParams['http']['content'] = $paramsStr;
			else
			  	$this->url .= '?' . $paramsStr;
		}
		
		$context = stream_context_create($httpParams);
		$fHandle = @fopen($this->url, 'rb', false, $context);
	
		if ($fHandle)
		{
			$this->meta = stream_get_meta_data($fHandle);
			$this->rawResponse = stream_get_contents($fHandle);
		}
		
		switch (strtoupper($this->format))
		{
    		case 'JSON':
      			$this->response = json_decode($this->rawResponse, true);
			break;
			case 'XML':
				$this->response = simplexml_load_string($this->rawResponse);
			break;
			default:
				$this->response = $this->rawResponse;
			break;
     	}
	}
	
	public static function request($url, $params = array(), $method = "GET", $format="JSON")
	{
		$request = new HttpClient($url);
		$request->params = $params;
		$request->method = $method;
		$request->format = $format;
		$request->send();
		return $request->response;
	}
}
?>
