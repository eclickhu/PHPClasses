<?php

include_once "nameformatter.php";
include_once "httpparams.php";
include_once "fileparams.php";
include_once "jsoncollection.php";

abstract class RestApi
{
	protected $routePrefixes = array
	(
		"POST" => "post",
		"GET" => "get",
		"DELETE" => "delete",
		"PUT" => "put"
	);
	
	protected $actionName = "action";
	protected $action;
	protected $method;
	
	protected $ccFunctionName;
	protected $usFunctionName;
	
	protected $getParams;
	protected $postParams;
	protected $putParams;
	protected $deleteParams;
	
	protected $fileParams;
	protected $allParams;
	
	public function route()
	{
		$this->loadMethod();
		$this->loadParams();
		$actionName = $this->actionName;
		$this->action = $this->allParams->$actionName;
		
		if(!isset($this->routePrefixes[$this->method]))
			return $this->onUnsupportedHttpMethod($this->method);
		
		if(!$this->action)
			return $this->onUndefiniedAction($this->action);
			
		$this->loadFunctionNames();
		return $this->callFunction();
	}
	
	protected function callFunction()
	{
		$ccFunctionName = $this->ccFunctionName;
		$usFunctionName = $this->usFunctionName;
		
		if(method_exists($this, $ccFunctionName))
			return $this->$ccFunctionName($this->allParams, $this->fileParams);
		else if(method_exists($this, $usFunctionName))
			return $this->$usFunctionName($this->allParams, $this->fileParams);
		return $this->onUndefiniedFunction($this->action);
	}
	
	protected function loadFunctionNames()
	{
		$methodPrefix = $this->routePrefixes[$this->method];
		
		if(NameFormatter::isCamelCase($this->action))
		{
			$this->ccFunctionName = $methodPrefix.ucfirst($this->action);
			$this->usFunctionName = NameFormatter::toSnakeCase($this->ccFunctionName);
		}
		
		if(NameFormatter::isSnakeCase($this->action))
		{
			$this->usFunctionName = $methodPrefix."_".$this->action;
			$this->ccFunctionName = NameFormatter::toCamelCase($this->usFunctionName);
		}
	}
	
	protected function loadMethod()
	{
		$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
	}
	
	protected function loadGetParams()
	{
		$this->getParams = new HttpParams($_GET);
	}
	
	protected function loadPostParams()
	{
		$this->getParams = new HttpParams($_POST);
	}
	
	protected function loadPutParams()
	{
		$params = array();
		parse_str(file_get_contents("php://input"),$params);
		$this->putParams = new HttpParams($params);
	}
	
	protected function loadDeleteParams()
	{
		$params = array();
		parse_str(file_get_contents("php://input"),$params);
		$this->deleteParams = new HttpParams($params);
	}
	
	protected function loadAllParams()
	{
		$putAndDelete = array();
		parse_str(file_get_contents("php://input"),$putAndDelete);
		$this->allParams = new HttpParams(array_merge($_POST, $_GET, $putAndDelete));
	}
	
	protected function loadFileParams()
	{
		$this->fileParams = new FileParams();
	}
	
	protected function loadParams()
	{
		$this->loadGetParams();
		$this->loadPostParams();
		$this->loadPutParams();
		$this->loadDeleteParams();
		$this->loadAllParams();
		$this->loadFileParams();
	}
	
	protected function onUnauthorized($user = "")
	{
		return $this->onError("Unauthorized user");
	}
	
	protected function onUndefiniedFunction($functionName)
	{
		return $this->onError("Undefined function: {$functionName}");
	}
	
	protected function onUndefiniedAction($actionName)
	{
		return $this->onError("Undefined action! You need to provide: '{$this->actionName}' parameter to determine which function to call!");
	}
	
	protected function onUnsupportedHttpMethod($methodName)
	{
		return $this->onError("Unsupported HTTP method {$methodName}");
	}
	
	protected function onError($error = true)
	{
		return new JSONCollection(array("error" => $error, "success" => false));
	}
}

?>