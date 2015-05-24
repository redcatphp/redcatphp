<?php
/*
 * fusion of
 * Dice 2.0-Transitional - 2012-2015 Tom Butler <tom@r.je> | http://r.je/dice.html
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

	private $rules = ['*' => ['shared' => false, 'constructParams' => [], 'shareInstances' => [], 'call' => [], 'inherit' => true, 'substitutions' => [], 'instanceOf' => null, 'newInstances' => []]];
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
	
	function extendRule($name, $key, $value, $push = null){
		if(!isset($push))
			$push = is_array($this->rules['*'][$key]);
		$rule = $this->getRule($name);
		if($key=='instanceOf'&&$rule===$this->rules['*'])
			$rule = $this->getRule($value);
		if($push){
			if(is_array($value)){
				$rule[$key] = array_merge($rule[$key],$value);
			}
			else{
				$rule[$key][] = $value;
			}
		}
		else{
			$rule[$key] = $value;
		}
		$this->rules[$name] = $rule;
	}
	function addRule($name, array $rule) {
		$oldRule = $this->getRule($name);
		if(isset($rule['instanceOf'])&&$this->rules['*']===$oldRule)
			$oldRule = $this->getRule($rule['instanceOf']);
		$this->rules[$name] = self::merge_recursive($oldRule, $rule);
	}
	function getRule($name) {
		if (isset($this->rules[$name])) return $this->rules[$name];
		foreach ($this->rules as $key => $rule) {
			if ($rule['instanceOf'] === null && $key !== '*' && is_subclass_of($name, $key) && $rule['inherit'] === true) return $rule;
		}
		return $this->rules['*'];
	}

	function create($name, array $args = [], $forceNewInstance = false, $share = []) {
		$instance = $name;
		if(!empty($args)){
			$instance .= '.'.self::hashArguments($args);
		}
		if(!$forceNewInstance&&isset($this->instances[$instance])) return $this->instances[$instance];
		if(empty($this->cache[$name])) $this->cache[$instance] = $this->getClosure($name, $this->getRule($name), $instance);
		return $this->cache[$name]($args, $share);
	}

	private function getClosure($name, array $rule, $instance){
		$class = new \ReflectionClass(isset($rule['instanceOf']) ? $rule['instanceOf'] : $name);
		$constructor = $class->getConstructor();
		$params = $constructor ? $this->getParams($constructor, $rule) : null;
		if ($rule['shared']) $closure = function (array $args, array $share) use ($class, $name, $constructor, $params, $instance) {
			$this->instances[$instance] = $class->newInstanceWithoutConstructor();
			if ($constructor) $constructor->invokeArgs($this->instances[$instance], $params($args, $share));
			return $this->instances[$instance];
		};
		else if ($params) $closure = function (array $args, array $share) use ($class, $params) { return (new \ReflectionClass($class->name))->newInstanceArgs($params($args, $share)); };
		else $closure = function () use ($class) { return new $class->name;	};

		return $rule['call'] ? function (array $args, array $share) use ($closure, $class, $rule) {
			$object = $closure($args, $share);
			foreach ($rule['call'] as $call) call_user_func_array([$object,$call[0]],$this->getParams($class->getMethod($call[0]), $rule)->__invoke($this->expand($call[1])));
			return $object;
		} : $closure;
	}

	private function expand($param, array $share = []) {
		if (is_array($param) && isset($param['instance'])) {
			return is_callable($param['instance']) ? call_user_func_array($param['instance'], (isset($param['params']) ? $this->expand($param['params']) : [$this])) : $this->create($param['instance'], [], false, $share);
		}
		else if (is_array($param)) foreach ($param as &$value) $value = $this->expand($value, $share);
		return $param;
	}

	private function getParams(\ReflectionMethod $method, array $rule) {
		$paramInfo = [];
		foreach ($method->getParameters() as $param) {
			$class = $param->getClass() ? $param->getClass()->name : null;
			$paramInfo[] = [$class, $param->allowsNull(), array_key_exists($class, $rule['substitutions']), in_array($class, $rule['newInstances'])];
		}
		return function (array $args, array $share = []) use ($paramInfo, $rule) {
			//if ($rule['shareInstances']) $share = array_merge($share, array_map([$this, 'create'], $rule['shareInstances']));
			if(!empty($rule['shareInstances'])){
				$shareInstances = [];
				foreach($rule['shareInstances'] as $v){
					if(isset($rule['substitutions'][$v])){
						$v = $rule['substitutions'][$v];
					}
					$new = in_array($v,$rule['newInstances']);
					$shareInstances[] = $this->create($v,[],$new);
				}
				$share = array_merge($share, $shareInstances);
			}
			if ($share || $rule['constructParams']) $args = array_merge($args, $this->expand($rule['constructParams']), $share);
			$parameters = [];

			foreach ($paramInfo as list($class, $allowsNull, $sub, $new)) {
				if ($args) foreach ($args as $i => $arg) {
					if ($class && $arg instanceof $class || ($arg === null && $allowsNull)) {
						$parameters[] = array_splice($args, $i, 1)[0];
						continue 2;
					}
				}
				if ($class) $parameters[] = $sub ? $this->expand(['instance'=>$rule['substitutions'][$class]], $share) : $this->create($class, [], $new, $share);
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
				return ['instance'=>(string)$param];
			break;
			case 'callback':
				$dic = $this;
				$str = (string)$param;
				return ['instance'=>function()use($dic,$str){
					$parts = explode('::', $str);
					$object = $dic->create(array_shift($parts));
					while ($var = array_shift($parts)){
						if (strpos($var, '(') !== false) {
							$args = explode(',', substr($var, strpos($var, '(')+1, strpos($var, ')')-strpos($var, '(')-1));
							$object = call_user_func_array([$object, substr($var, 0, strpos($var, '('))], ($args[0] == null) ? [] : $args);
						}
						else $object = $object->$var;
					}
					return $object;
				}];
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
	function loadXml($xml){
		if (!($xml instanceof \SimpleXmlElement))
			$xml = simplexml_load_file($xml);
		foreach ($xml->class as $key => $value) {
			$rule = [];
			$rule['shared'] = (((string)$value['shared']) == 'true');
			$rule['inherit'] = (((string)$value['inherit']) == 'false') ? false : true;
			if($value->call){
				foreach($value->call as $name=>$call){
					$callArgs = [];
						foreach ($call as $key => $param)
							$callArgs[] = $this->getComponent($key,$param);
					$rule['call'][] = [(string) $call['method'], $callArgs];
				}
			}
			if (isset($value['instanceOf']))
				$rule['instanceOf'] = (string) $value['instanceOf'];
			if ($value['newInstances']){
				foreach(explode(',',$value['newInstances']) as $ni){
					$rule['newInstances'][] = (string) $ni;
				}
			}
			if ($value->substitution)
				foreach ($value->substitution as $use)
					$rule['substitutions'][(string) $use['as']] = $this->getComponent('instance',(string) $use['use']);
			if ($value->constructParams){
				foreach ($value->constructParams->children() as $key=>$param){
					$rule['constructParams'][] = $this->getComponent($key,$param);
				}
			}
			if ($value->shareInstance){
				foreach(explode(',',$value['shareInstance']) as $ni){
					$rule['shareInstances'][] = (string) $share;
				}
			}
			$this->addRule((string) $value['name'], $rule);
		}
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
	private static function merge_recursive(){
		$args = func_get_args();
		$merged = array_shift($args);
		foreach($args as $array2){
			if(!is_array($array2)){
				continue;
			}
			foreach($array2 as $key => &$value){
				if(is_array($value)&&isset($merged [$key])&&is_array($merged[$key])){
					$merged[$key] = self::merge_recursive($merged[$key],$value);
				}
				else{
					$merged[$key] = $value;
				}
			}
		}
		return $merged;
	}
}