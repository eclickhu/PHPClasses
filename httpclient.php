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
		$httpParams = array
		(
    		'http' => array
			(
      			'method' => $this->method,
      			'ignore_errors' => true
			)
    	);
		
		if ($this->params && is_array($this->params)) 
		{
			$paramsStr = http_build_query($this->params);
			if ($this->method == 'POST') 
			  	$httpParams['http']['content'] = $paramsStr;
			else
			  	$this->url .= '?' . $paramsStr;
		}
		
		$context = stream_context_create($httpParams);
		$fHandle = fopen($this->url, 'rb', false, $context);
	
		if ($fHandle)
		{
			$this->meta = stream_get_meta_data($fHandle);
			$this->rawResponse = stream_get_contents($fHandle);
		}
		
		switch (strtoupper($this->format))
		{
    		case 'json':
      			$this->response = json_decode($this->rawResponse);
			break;
			case 'xml':
				$this->response = simplexml_load_string($this->rawResponse);
			break;
			default:
				$this->response = $this->rawResponse;
			break;
     	}
	}
}
?>
