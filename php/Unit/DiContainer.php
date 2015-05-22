<?php
/*
 * fusion of
 * Dice 1.4 - 2012-2015 Tom Butler <tom@r.je> | http://r.je/dice.html
 *		for clean decoupled dependencies resolution
 * and Pimple 3 - 2009 Fabien Potencier | http://pimple.sensiolabs.org
 *		for arbitrary data and manual hook
 * with Surikat remixs and addons
 *		for powerfull API and format
 */

namespace Unit;

class DiContainer implements \ArrayAccess{
	private $values = [];
	private $factories;
	private $protected;
	private $frozen = [];
	private $raw = [];
	private $keys = [];

	private $rules = [];
	private $cache = [];
	private $instances = [];
	
	private static $instance;
	
	static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new DiContainer();
			self::$instance->instances[__CLASS__] = self::$instance;
		}
		return self::$instance;
	}
		
	function __construct(array $values = []){
		$this->factories = new \SplObjectStorage();
		$this->protected = new \SplObjectStorage();
		foreach ($values as $key => $value) {
			$this->offsetSet($key, $value);
		}
	}

	function offsetSet($id, $value){
		if (isset($this->frozen[$id])) {
			throw new \RuntimeException(sprintf('Cannot override frozen service "%s".', $id));
		}
		$this->values[$id] = $value;
		$this->keys[$id] = true;
	}

	function offsetGet($id){
		if(!isset($this->keys[$id])){
				$this[$id] = $this->create($id);
		}
		if (
				isset($this->raw[$id])
				|| !is_object($this->values[$id])
				|| isset($this->protected[$this->values[$id]])
				|| !method_exists($this->values[$id], '__invoke')
		) {
				return $this->values[$id];
		}
		if (isset($this->factories[$this->values[$id]])) {
				return $this->values[$id]($this);
		}
		$raw = $this->values[$id];
		$val = $this->values[$id] = $raw($this);
		$this->raw[$id] = $raw;
		$this->frozen[$id] = true;
		return $val;
	}

	function offsetExists($id){
		return isset($this->keys[$id]);
	}

	function offsetUnset($id){
		if (isset($this->keys[$id])) {
			if (is_object($this->values[$id])) {
				unset($this->factories[$this->values[$id]], $this->protected[$this->values[$id]]);
			}
			unset($this->values[$id], $this->frozen[$id], $this->raw[$id], $this->keys[$id]);
		}
	}

	function factory($callable){
		if (!is_object($callable) || !method_exists($callable, '__invoke')) {
			throw new \InvalidArgumentException('Service definition is not a Closure or invokable object.');
		}
		$this->factories->attach($callable);
		return $callable;
	}
	
	function protect($callable){
		if (!is_object($callable) || !method_exists($callable, '__invoke')) {
			throw new \InvalidArgumentException('Callable is not a Closure or invokable object.');
		}
		$this->protected->attach($callable);
		return $callable;
	}

	function raw($id){
		if (!isset($this->keys[$id])) {
			throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
		}
		if (isset($this->raw[$id])) {
			return $this->raw[$id];
		}
		return $this->values[$id];
	}

	function extend($id, $callable){
		if (!isset($this->keys[$id])) {
			throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
		}
		if (!is_object($this->values[$id]) || !method_exists($this->values[$id], '__invoke')) {
			throw new \InvalidArgumentException(sprintf('Identifier "%s" does not contain an object definition.', $id));
		}
		if (!is_object($callable) || !method_exists($callable, '__invoke')) {
			throw new \InvalidArgumentException('Extension service definition is not a Closure or invokable object.');
		}
		$factory = $this->values[$id];
		$extended = function ($c) use ($callable, $factory) {
			return $callable($factory($c), $c);
		};
		if (isset($this->factories[$factory])) {
			$this->factories->detach($factory);
			$this->factories->attach($extended);
		}
		return $this[$id] = $extended;
	}

	function keys(){
		return array_keys($this->values);
	}

	function register($provider, array $values = []){
		$provider->register($this);
		foreach ($values as $key => $value) {
				$this[$key] = $value;
		}
		return $this;
	}
	
	function setRule($name, DiRule $rule) {
		$name = ltrim(strtolower($name), '\\');
		$this->rules[$name] = $rule;
	}
	function addRule($name, $rule = [], $push = false) {
		$rule = (object)$rule;
		$name = ltrim(strtolower($name), '\\');
		if(!isset($this->rules[$name])&&isset($rule->instanceOf)){
			$cascade = $rule->instanceOf;
		}
		else{
			$cascade = $name;
		}
		$cascade = clone $this->getRule($cascade);
		foreach($rule as $k=>$v){
			if($k=='substitutions'){
				foreach($v as $as=>$use){
					if(!is_object($as)){
						$v[$as] = new DiInstance($use);
					}
				}
			}
			if(($k=='newInstances'||$k=='shareInstances')&&is_string($v)){
				$v = explode(',',$v);
			}
			if(is_array($cascade->$k)){
				if($push){
					foreach($v as $_v){
						$cascade->{$k}[] = $_v;
					}
				}
				else{
					foreach($v as $_k=>$_v){
						$cascade->{$k}[$_k] = $_v;
					}
				}
			}
			else{
				$cascade->$k = $v;
			}
		}
		$this->setRule($name,$cascade);
		return $cascade;
	}

	function getRule($name) {
		if (isset($this->rules[strtolower(ltrim($name, '\\'))])) return $this->rules[strtolower(ltrim($name, '\\'))];
		foreach ($this->rules as $key => $rule) {
			if (($rule->instanceOf === null || $rule->instanceOf===$name) && $key !== '*' && is_subclass_of($name, $key) && $rule->inherit === true){
				return $rule;
			}
		}
		return isset($this->rules['*']) ? $this->rules['*'] : $this->rules['*'] = new DiRule;
	}

	function create($component, array $args = [], $forceNewInstance = false, $share = []) {
		$instance = $component;
		if(func_num_args()>1){
			$instance .= '.'.self::hashArguments($args);
		}
		if (!$forceNewInstance && isset($this->instances[$instance]))
			return $this->instances[$instance];
		if (empty($this->cache[$instance])) {
			$rule = $this->getRule($component);
			$class = new \ReflectionClass($rule->instanceOf ?: $component);
			$constructor = $class->getConstructor();
			$params = $constructor ? $this->getParams($constructor, $rule) : null;

			$this->cache[$instance] = function($args, $share) use ($instance, $rule, $class, $constructor, $params) {
				if ($rule->shared) {
					$this->instances[$instance] = $object = $class->newInstanceWithoutConstructor();
					if ($constructor) $constructor->invokeArgs($object, $params($args, $share));
				}
				else $object = $params ? (new \ReflectionClass($class->name))->newInstanceArgs($params($args, $share)) : new $class->name;
				if ($rule->call) foreach ($rule->call as $call) $class->getMethod($call[0])->invokeArgs($object, call_user_func($this->getParams($class->getMethod($call[0]), $rule), $this->expand($call[1])));
				return $object;
			};
		}
		return $this->cache[$instance]($args, $share);
	}

	private function expand($param, array $share = [], $forceNewInstance=false) {
		if (is_array($param)) foreach ($param as &$key) $key = $this->expand($key, $share); 
		else if ($param instanceof DiInstance) return is_callable($param->name) ? call_user_func($param->name, $this, $share) : $this->create($param->name, [], $forceNewInstance, $share);
		return $param;
	}

	private function getParams(\ReflectionMethod $method, DiRule $rule) {
		$paramInfo = [];
		foreach($method->getParameters() as $param){
			$class = $param->getClass() ? $param->getClass()->name : null;
			$paramInfo[] = [$class, $param->allowsNull(), array_key_exists($class, $rule->substitutions), in_array($class, $rule->newInstances)];
		}
		return function($args, $share = []) use ($paramInfo, $rule){
			if ($rule->shareInstances){
				$shareInstances = [];
				foreach($rule->shareInstances as $v){
					if(isset($rule->substitutions[$v])){
						$v = $rule->substitutions[$v]->name;
					}
					$new = in_array($v,$rule->newInstances);
					$shareInstances[] = $this->create($v,[],$new);
				}
				$share = array_merge($share, $shareInstances);
			}
			if ($share || $rule->constructParams) $args = array_merge($args, $this->expand($rule->constructParams, $share), $share);
			$parameters = [];

			foreach ($paramInfo as list($class, $allowsNull, $sub, $new)) {
				if ($args && $count = count($args)) for ($i = 0; $i < $count; $i++) {
					if ($class && $args[$i] instanceof $class || ($args[$i] === null && $allowsNull)) {
						$parameters[] = array_splice($args, $i, 1)[0];
						continue 2;
					}
				}
				if ($class) $parameters[] = $sub ? $this->expand($rule->substitutions[$class], $share, $new) : $this->create($class, [], $new, $share);
				else if ($args) $parameters[] = $this->expand(array_shift($args));
			}
			return $parameters;
		};
	}
	
	private function getComponent($key, $param){
		switch($key){
			case 'concat':
				$r = '';
				foreach($param->children() as $k=>$p){
					$r .= $this->getComponent($k,$p);
				}
				return $r;
			break;
			case 'instance':
				return new DiInstance((string)$param);
			break;
			case 'callback':
				return [new DiCallback((string)$param,$this), 'run'];
			break;
			case 'eval':
				return eval(' return '.$param.';');
			break;
			case 'constant':
				return constant((string)$param);
			break;
			default:
			case 'string':
				return (string)$param;
			break;
		}
	}
	function loadXml($xml,$push=false){
		if (!($xml instanceof \SimpleXmlElement))
			$xml = simplexml_load_file($xml);
		foreach ($xml->class as $key => $value) {
			$rule = $this->createRule((string) $value['name']);
			$rule->shared = (((string)$value['shared']) == 'true');
			$rule->inherit = (((string)$value['inherit']) == 'false') ? false : true;
			if($value->call){
				foreach($value->call as $name=>$call){
					$callArgs = [];
						foreach ($call as $key => $param)
							$callArgs[] = $this->getComponent($key,$param);
					$rule->call[] = [(string) $call['method'], $callArgs];
				}
			}
			if (isset($value['instanceof']))
				$rule->instanceOf = (string) $value['instanceof'];
			if ($value['newinstances']){
				foreach(explode(',',$value['newinstances']) as $ni){
					$rule->newInstances[] = (string) $ni;
				}
			}
			if ($value->substitute)
				foreach ($value->substitute as $use)
					$rule->substitutions[(string) $use['as']] = new DiInstance((string) $use['use']);
			if ($value->construct){
				foreach ($value->construct->children() as $key=>$param){
					$rule->constructParams[] = $this->getComponent($key,$param);
				}
			}
			if ($value->shareinstance){
				foreach(explode(',',$value['shareinstance']) as $ni){
					$rule->shareInstances[] = (string) $share;
				}
			}
			$this->addRule((string) $value['name'], $rule, $push);
		}
	}
	function createRule($name){
		return new DiRule($name);
	}
	
	private static function hashArguments($args){
		static $storage = null;
		if(!isset($storage))
			$storage = new \SplObjectStorage();
		$hash = [];
		foreach($args as $arg){
			if(is_array($arg)){
				$hash[] = self::hashArguments($arg);
			}
			elseif(is_object($arg)){
				$storage->attach($arg);
				$hash[] = spl_object_hash($arg);
			}
			else{
				$hash[] = sha1($arg);
			}
		}
		return sha1(implode('.',$hash));
	}
}