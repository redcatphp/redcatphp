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
	
	static function load(array $map,$freeze=false,$file=null){
		if($freeze){
			if(!isset($file)){
				$file = __DIR__.'/'.__CLASS__.'.svar';
			}
			if(is_file($file)){
				self::$instance = unserialize(file_get_contents($file));
				self::$instance->instances[__CLASS__] = self::$instance;
			}
			else{
				array_map([self::getInstance(),'loadXml'],$map);
				$dir = dirname($file);
				if(!is_dir($dir))
					@mkdir($dir,0777,true);
				file_put_contents($file,serialize(self::$instance));
			}
		}
		else{
			array_map([self::getInstance(),'loadXml'],$map);
		}
		return self::$instance;
	}
	
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
	
	function objectify($a){
		if(is_object($a))
			return $a;
		if(is_array($a)){
			if(is_array($a[0])){
				$a[0] = $this->objectify($a[0]);
				return $a;
			}
			else{
				$args = $a;
				$s = array_shift($args);
			}
		}
		else{
			$args = [];
			$s = $a;
		}
		if(strpos($s,'new:')===0)
			$a = $this->create(substr($s,4),$args);
		return $a;
	}
	function extendRule($name, $key, $value, $push = null){
		if(!isset($push))
			$push = is_array($this->rules['*'][$key]);
		$rule = $this->getRule($name);
		if($key==='instanceOf'&&is_string($value)&&$rule===$this->rules['*'])
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
		if(isset($rule['instanceOf'])&&is_string($rule['instanceOf'])&&$this->rules['*']===$oldRule)
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
		if(empty($this->cache[$name])) $this->cache[$name] = $this->getClosure($name, $this->getRule($name), $instance);
		return $this->cache[$name]($args, $share);
	}

	private function getClosure($name, array $rule, $instance){
		$class = new \ReflectionClass(isset($rule['instanceOf']) ? $rule['instanceOf'] : $name);
		$constructor = $class->getConstructor();
		$params = $constructor ? $this->getParams($constructor, $rule) : null;
		if ($rule['shared']){
			$closure = function (array $args, array $share) use ($class, $name, $constructor, $params, $instance) {
				$this->instances[$instance] = $class->newInstanceWithoutConstructor();
				if ($constructor) $constructor->invokeArgs($this->instances[$instance], $params($args, $share));
				return $this->instances[$instance];
			};
		}
		else if ($params){
			$closure = function (array $args, array $share) use ($class, $params) {
				return (new \ReflectionClass($class->name))->newInstanceArgs($params($args, $share));
			};
		}
		else{
			$closure = function () use ($class) { return new $class->name;	};
		 }
		return $rule['call'] ? function (array $args, array $share) use ($closure, $class, $rule) {
			$object = $closure($args, $share);
			foreach ($rule['call'] as $call) call_user_func_array([$object,$call[0]],$this->getParams($class->getMethod($call[0]), $rule)->__invoke($this->expand(isset($call[1])?$call[1]:[])));
			return $object;
		} : $closure;
	}

	private function expand($param, array $share = []) {
		if (is_array($param)){
			if(isset($param['instance'])) {
				if(is_callable($param['instance'])){
					return call_user_func_array($param['instance'], (isset($param['params']) ? $this->expand($param['params']) : [$this]));
				}
				else{
					return $this->create($param['instance'], [], false, $share);
				}
			}
			else{
				foreach ($param as &$value) $value = $this->expand($value, $share);
			}
		}
		return $param;
	}

	private function getParams(\ReflectionMethod $method, array $rule) {
		$paramInfo = [];
		foreach ($method->getParameters() as $param) {
			$class = $param->getClass() ? $param->getClass()->name : null;
			$paramInfo[] = [$class, $param->allowsNull(), array_key_exists($class, $rule['substitutions']), in_array($class, $rule['newInstances']),$param->getName(),$param->isDefaultValueAvailable()?$param->getDefaultValue():null];
		}
		return function (array $args, array $share = []) use ($paramInfo, $rule) {
			if(!empty($rule['shareInstances'])){
				$shareInstances = [];
				foreach($rule['shareInstances'] as $v){
					if(isset($rule['substitutions'][$v])){
						$v = $rule['substitutions'][$v];
					}
					if(is_object($v)){
						$shareInstances[] = $v;
					}
					else{
						$new = in_array($v,$rule['newInstances']);
						$shareInstances[] = $this->create($v,[],$new);
					}
				}
				$share = array_merge($share, $shareInstances);
			}
			if($share||!empty($rule['constructParams'])){
				$nArgs = $args;
				foreach($this->expand($rule['constructParams']) as $k=>$v){
					if(is_integer($k)){
						$nArgs[] = $v;
					}
					elseif(!isset($args[$k])){
						$nArgs[$k] = $v;
					}
				}
				$args = array_merge($nArgs, $share);
			}
			$parameters = [];
			if (!empty($args)){
				foreach ($paramInfo as $j=>list(,,,,$name,$default)) {
					if(false!==$offset=array_search($name, array_keys($args),true)){
						$parameters[$j] = current(array_splice($args, $offset, 1));
					}
				}
			}
			foreach ($paramInfo as $j=>list($class, $allowsNull, $sub, $new, $name, $default)) {
				if(array_key_exists($j,$parameters))
					continue;
				if($class){
					if (!empty($args)){
						foreach($args as $i=>$arg){
							if($arg instanceof $class || ($arg === null && $allowsNull) ){
								$parameters[$j] = $arg;
								unset($args[$i]);
								continue 2;
							}
						}
					}
					if($sub){
						if(is_string($rule['substitutions'][$class])){
							$rule['substitutions'][$class] = ['instance'=>$rule['substitutions'][$class]];
						}
						$parameters[$j] = $this->expand($rule['substitutions'][$class], $share);
					}
					else{
						$parameters[$j] = $this->create($class, [], $new, $share);
					}
				}
				elseif(!empty($args)){
					$parameters[$j] = $this->expand(array_shift($args));
				}
				else{
					$parameters[$j] = $default;
				}
			}
			ksort($parameters);
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
					foreach($call as $key=>$param){
						$this->buildXmlParam($key,$param,$callArgs);
					}
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
				foreach($value->constructParams->attributes() as $key=>$param){
					$this->buildXmlParam($key,$param,$rule['constructParams']);
				}
				foreach($value->constructParams->children() as $key=>$param){
					$this->buildXmlParam($key,$param,$rule['constructParams']);
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
	private function buildXmlParam($key,$param,&$rulePart){
		$type = $this->typeofParam($key,$param);
		$assoc = $this->associativeParam($key,$param);
		$component = $this->getComponent($type,$param);
		if($assoc){
			$rulePart[$assoc] = $component;
		}
		else{
			$rulePart[] = $component;
		}
	}
	private function typeofParam($key,$param=null){
		if($param instanceof \SimpleXmlElement&&isset($param['type']))
			return (string)$param['type'];
		elseif(false!==$p=strpos($key,'-'))
			return substr($key,0,$p);
		else
			return $key;
	}
	private function associativeParam($key,$param){
		if(isset($param['name']))
			return (string)$param['name'];
		elseif(false!==$p=strpos($key,'-'))
			return substr($key,$p+1);
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
			foreach($array2 as $key => $value){
				if(is_array($value)&&isset($merged[$key])&&is_array($merged[$key])){
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