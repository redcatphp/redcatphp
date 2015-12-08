<?php
namespace RedCat\Plugin\Http;
use Psr\Http\Message\ServerRequestInterface;
class Cookie implements \ArrayAccess{
	protected $serverRequest;
	protected $data;
	function __construct(ServerRequestInterface $serverRequest){
		$this->serverRequest = $serverRequest;
		$this->data = $this->getCookieParams();
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function offsetGet($k){
		return isset($this->data[$k])?$this->data[$k]:null;
	}
	function offsetSet($k,$v){
		$this->data[$k] = $v;
	}
	function offsetUnset($k){
		unset($this->data[$k]);
	}
}