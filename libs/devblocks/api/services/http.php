<?php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\RequestOptions;

class _DevblocksHttpService {
	static $instance = null;
	private static $_client = null;
	
	private function __construct() {}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksHttpService();
		}
		
		return self::$instance;
	}
	
	private function _configure_defaults() {
		return function(callable $handler) {
			return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
				if(!array_key_exists(RequestOptions::HTTP_ERRORS, $options))
					$options[RequestOptions::HTTP_ERRORS] = false;
				
				if(!array_key_exists(RequestOptions::CONNECT_TIMEOUT, $options))
					$options[RequestOptions::CONNECT_TIMEOUT] = 10;
				
				if(!array_key_exists(RequestOptions::TIMEOUT, $options))
					$options[RequestOptions::TIMEOUT] = 30;
				
				if(defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY) {
					$options[RequestOptions::PROXY] = [
						'http' => DEVBLOCKS_HTTP_PROXY,
						'https' => DEVBLOCKS_HTTP_PROXY,
					];
				}
				return $handler($request, $options);
			};
		};
	}
	
	function getClient() : GuzzleHttp\Client {
		if(self::$_client)
			return self::$_client;
		
		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		
		// Register proxy middleware
		$stack->push($this->_configure_defaults());
		
		self::$_client = new GuzzleHttp\Client(['handler' => $stack]);
		
		return self::$_client;
	}
	
	/**
	 * 
	 * @param RequestInterface $request
	 * @param array $options
	 * @param string $error
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	function sendRequest(\Psr\Http\Message\RequestInterface $request, array $options=[], &$error=null, &$status_code=0) {
		$client = $this->getClient();
		
		try {
			$response = $client->send($request, $options);
			$status_code = $response->getStatusCode();
			return $response;
			
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			$error = $e->getMessage();
			
			if(null != ($response = $e->getResponse()))
				$status_code = $response->getStatusCode();
			
			return false;
		}
	}
	
	/**
	 * 
	 * @param ResponseInterface $response
	 * @param string $error
	 * @return string|false
	 */
	function getResponseAsJson(ResponseInterface $response, &$error=null) {
		// [TODO] Check status code
		// [TODO] Check content-type?
		
		if(false === ($json = @json_decode($response->getBody()->getContents(), true)))
			return false;
		
		return $json;
	}
}