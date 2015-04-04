<?php namespace Surikat\Component\Config;
use Surikat\Component\DependencyInjection\MutatorTrait;
//inspired from Zend
class Config implements \Countable, \Iterator, \ArrayAccess {
	use MutatorTrait;
	protected $allowModifications;
    protected $count;
    protected $data = [];
    protected $skipNextIteration;
    protected $Loader;
    function __construct($array, $allowModifications=true, $loader=null){
        $this->allowModifications = (bool) $allowModifications;
		if(isset($loader)){
			$this->Loader = $loader;
		}
		elseif(is_string($array)){
			$this->Loader = $this->getDependency('__Loader',[$array]);
		}
		else{
			$this->Loader = $this->getDependency('__Loader');
		}
        if(is_string($array)){
			$array = $this->Loader->load();
		}
        foreach($array as $key => $value){
            if (is_array($value))
                $this->data[$key] = new static($value, $this->allowModifications);
            else
                $this->data[$key] = $value;
            $this->count++;
        }
    }
    function get($name, $default = null){
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return $default;
    }
	function store(){
		$this->Loader->putContents($this);
	}
    function __get($name){
        return $this->get($name);
    }
    function __set($name, $value){
        if ($this->allowModifications) {
            if (is_array($value)) {
                $value = new static($value, true);
            }
            if (null === $name) {
                $this->data[] = $value;
            } else {
                $this->data[$name] = $value;
            }
            $this->count++;
        } else {
            throw new \RuntimeException('Config is read only');
        }
    }
    function __clone(){
        $array = array();
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = clone $value;
            } else {
                $array[$key] = $value;
            }
        }
        $this->data = $array;
    }
    function toArray(){
        $array = array();
        $data  = $this->data;
        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }
    function __isset($name){
        return isset($this->data[$name]);
    }
    function __unset($name){
        if (!$this->allowModifications) {
            throw new \InvalidArgumentException('Config is read only');
        } elseif (isset($this->data[$name])) {
            unset($this->data[$name]);
            $this->count--;
            $this->skipNextIteration = true;
        }
    }
    function count(){
        return $this->count;
    }
    function current(){
        $this->skipNextIteration = false;
        return current($this->data);
    }
    function key(){
        return key($this->data);
    }
    function next(){
        if ($this->skipNextIteration) {
            $this->skipNextIteration = false;
            return;
        }
        next($this->data);
    }
    function rewind(){
        $this->skipNextIteration = false;
        reset($this->data);
    }
    function valid(){
        return ($this->key() !== null);
    }
    function offsetExists($offset){
        return $this->__isset($offset);
    }
    function offsetGet($offset){
        return $this->__get($offset);
    }
    function offsetSet($offset, $value){
        $this->__set($offset, $value);
    }
    function offsetUnset($offset){
        $this->__unset($offset);
    }
    function merge(Config $merge){
        foreach ($merge as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                if (is_int($key)) {
                    $this->data[] = $value;
                } elseif ($value instanceof self && $this->data[$key] instanceof self) {
                    $this->data[$key]->merge($value);
                } else {
                    if ($value instanceof self) {
                        $this->data[$key] = new static($value->toArray(), $this->allowModifications);
                    } else {
                        $this->data[$key] = $value;
                    }
                }
            } else {
                if ($value instanceof self) {
                    $this->data[$key] = new static($value->toArray(), $this->allowModifications);
                } else {
                    $this->data[$key] = $value;
                }

                $this->count++;
            }
        }
        return $this;
    }
    function setReadOnly(){
        $this->allowModifications = false;
        foreach ($this->data as $value) {
            if($value instanceof self){
                $value->setReadOnly();
            }
        }
    }
    function isReadOnly(){
        return !$this->allowModifications;
    }
}