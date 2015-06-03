<?php
/*
 * inspired from a fusion of
 * Dice 2.0-Transitional - 2012-2015 Tom Butler <tom@r.je> | http://r.je/dice.html
 *		for clean decoupled dependencies resolution
 * and Pimple 3 - 2009 Fabien Potencier | http://pimple.sensiolabs.org
 *		for arbitrary data and manual hook
 * with lot of Surikat improvements, addons and remixs
 *		for powerfull API, lazy load cascade rules resolution,
 *		full registry implementation, full xml API, freeze optimisation
 */

namespace Unit;

class Di implements \ArrayAccess{
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
	
	static function make($name, $args = [], $forceNewInstance = false, $share = []){
		return self::getInstance()->create($name, $args, $forceNewInstance, $share);
	}
	
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
			self::$instance = new self;
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
		if(isset($this->rules[$name]))
			$rule = $this->rules[$name];
		elseif($key==='instanceOf'&&is_string($value)&&isset($this->rules[$value]))
			$rule = $this->rules[$value];
		else
			$rule = [];
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
	function addRule($name, array $rule){
		if(isset($this->rules[$name])){
			$this->rules[$name] = self::merge_recursive($this->rules[$name], $rule);
		}
		elseif(isset($rule['instanceOf'])&&is_string($rule['instanceOf'])&&isset($this->rules[$rule['instanceOf']])){
			$this->rules[$name] = self::merge_recursive($this->rules[$rule['instanceOf']], $rule);
		}
		else{
			$this->rules[$name] = $rule;
		}
	}
	function getRule($name){
		$rules = $this->rules;
		$rule = $rules['*'];
		unset($rules['*']);
		
		if(preg_match('(^(?>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\?)+$)', $name)){
			$class = new \ReflectionClass($name);
			$classNames = [];
			$interfaces = $class->getInterfaceNames();
			do{
				$classNames[] = $class->getName();
			}while($class=$class->getParentClass());
			$classNames = array_merge($classNames,$interfaces);
			$rules = array_intersect_key($rules, array_flip($classNames));
			uksort($rules,function($a,$b)use($classNames){
				return array_search($a,$classNames)<array_search($b,$classNames);
			});
		}
		foreach($rules as $key=>$r){
			if($rule['instanceOf']===null&&(!isset($r['inherit'])||$r['inherit']===true)){
				$rule = self::merge_recursive($rule, $r);
			}
		}
		if(isset($this->rules[$name]))
			$rule = self::merge_recursive($rule, $this->rules[$name]);
		return $rule;
	}

	function create($name, $args = [], $forceNewInstance = false, $share = []){
		if(!is_array($args))
			$args = (array)$args;
		$instance = $name;
		if($p=strpos($name,':')){
			$this->addRule($name,['instanceOf'=>substr($name,0,$p),'shared'=>true]);
			if(substr($instance,$p+1)==='$')
				$instance = $name.':'.self::hashArguments($args);
		}
		if(!$forceNewInstance&&isset($this->instances[$instance])) return $this->instances[$instance];
		if(empty($this->cache[$name])) $this->cache[$name] = $this->getClosure($name, $this->getRule($name), $instance);
		return $this->cache[$name]($args, $share);
	}
	
	function share($obj,$instance=null){
		if(!isset($instance))
			$instance = get_class($obj);
		elseif(is_array($instance))
			$instance = get_class($obj).':'.self::hashArguments($instance);
		$this->instances[$instance] = $obj;
	}
	
	private function getClosure($name, array $rule, $instance){
		$class = new \ReflectionClass(isset($rule['instanceOf']) ? $rule['instanceOf'] : $name);
		$constructor = $class->getConstructor();
		$params = $constructor ? $this->getParams($constructor, $rule) : null;
		if($rule['shared']){
			$closure = function (array $args, array $share) use ($class, $name, $constructor, $params, $instance) {
				$this->instances[$instance] = $class->newInstanceWithoutConstructor();
				if ($constructor) $constructor->invokeArgs($this->instances[$instance], $params($args, $share));
				return $this->instances[$instance];
			};
		}
		else if ($params){
			$closure = function (array $args, array $share) use ($class, $params, $class){
				return $class->newInstanceArgs($params($args, $share));
			};
		}
		else{
			$closure = function () use ($class) { return new $class->name;	};
		}
		return !empty($rule['call']) ? function (array $args, array $share) use ($closure, $class, $rule) {
			$object = $closure($args, $share);
			foreach ($rule['call'] as $k=>$call){
				if(!is_integer($k)){
					$call = [$k,(array)$call];
				}
				call_user_func_array([$object,$call[0]],$this->getParams($class->getMethod($call[0]), $rule)->__invoke($this->expand(isset($call[1])?$call[1]:[])));
			}
			return $object;
		} : $closure;
	}

	private function expand($param, array $share = []) {
		if (is_array($param)){
			foreach($param as &$value){
				$value = $this->expand($value, $share);
			}
		}
		elseif($param instanceof DiExpand){
			$param = $param($this,$share);
		}
		return $param;
	}

	private function getParams(\ReflectionMethod $method, array $rule) {
		$paramInfo = [];
		foreach ($method->getParameters() as $param) {
			try{
				$class = $param->getClass() ? $param->getClass()->name : null;
			}
			catch(\ReflectionException $e){
				if($param->allowsNull()) $class = null;
				else throw $e;
			}
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
			foreach($paramInfo as $j=>list($class, $allowsNull, $sub, $new, $name, $default)){
				if(array_key_exists($j,$parameters))
					continue;
				if($class){
					if (!empty($args)){
						foreach($args as $i=>$arg){
							if($arg instanceof $class || ($arg === null && $allowsNull) ){
								$parameters[$j] = &$args[$i];
								unset($args[$i]);
								continue 2;
							}
						}
					}
					if($sub){
						if(is_string($rule['substitutions'][$class]))
							$parameters[$j] = $this->create($rule['substitutions'][$class],[],false,$share);
						else
							$parameters[$j] = $rule['substitutions'][$class];
					}
					else{
						$parameters[$j] = $this->create($class, [], $new, $share);
					}
				}
				elseif(!empty($args)){
					reset($args);
					$k = key($args);
					$parameters[$j] = &$args[$k];
					unset($args[$k]);
					$parameters[$j] = $this->expand($parameters[$j]);
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
				return new DiExpand((string)$param);
			break;
			case 'callback':
				$dic = $this;
				$str = (string)$param;
				return new DiExpand(function()use($dic,$str){
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
				});
			break;
			case 'eval':
				return eval(' return '.$param.';');
			break;
			case 'constant':
				return constant((string)$param);
			break;
			case 'int':
			case 'integer':
				return (int)$param;
			break;
			case 'boolean':
			case 'bool':
				return ((string)$param)==='true'||((string)$param)==='1';
			break;
			case 'string':
				return (string)$param;
			break;
			case 'config':
				$param = explode('.',(string)$param);
				$k = array_shift($param);
				if(!isset($this->keys[$k]))
					return;
				$v = $this[$k];
				while($k = array_shift($param)){
					if(!isset($v[$k]))
						return;
					$v = $v[$k];
				}
				return $v;
			break;
			default:
				$param = (string)$param;
				if(((int)$param)===$param){
					$param = (int)$param;
				}
				elseif($param==='true'||$param==='false'){
					$param = (bool)$param;
				}
				return $param;
			break;
		}
	}
	function loadXml($xml){
		if (!($xml instanceof \SimpleXmlElement))
			$xml = simplexml_load_file($xml);
		foreach($xml as $key=>$value){
			if($key==='class'){
				$this->defineClass($value);
			}
			else{
				$this->defineOffset($key,$value);
			}
		}
	}
	private function defineOffset($offset,$value){
		if(!isset($this->keys[$offset])){
			$this->keys[$offset] = true;
		}
		foreach($value->attributes() as $key=>$param){
			$this->buildXmlParam($key,$param,$this->values[$offset],true);
		}
		foreach($value->children() as $key=>$param){
			$this->buildXmlParam($key,$param,$this->values[$offset]);
		}
	}
	private function defineClass($value){
		$rule = [];
		$rule['shared'] = ((string)$value['shared'])=='true';
		$rule['inherit'] = (((string)$value['inherit']) == 'false') ? false : true;
		if($value->call){
			foreach($value->call as $name=>$call){
				$callArgs = [];
				foreach($call->attributes() as $key=>$param){
					$this->buildXmlParam($key,$param,$callArgs,true);
				}
				foreach($call->children() as $key=>$param){
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
				$this->buildXmlParam($key,$param,$rule['constructParams'],true);
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
	private function buildXmlParam($key,$param,&$rulePart,$forceAssoc=false){
		$type = $this->typeofParam($key,$param);
		$assoc = $this->associativeParam($key,$param,$forceAssoc);
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
	private function associativeParam($key,$param,$forceAssoc=false){
		if(isset($param['name']))
			return (string)$param['name'];
		elseif(false!==$p=strpos($key,'-'))
			return substr($key,$p+1);
		elseif($forceAssoc)
			return $key;
	}
	private static function hashArguments($args){
		static $storage = null;
		if(!isset($storage))
			$storage = new \SplObjectStorage();
		$hash = [];
		ksort($args);
		foreach($args as $k=>$arg){
			if(is_array($arg)){
				$h = self::hashArguments($arg);
			}
			elseif(is_object($arg)){
				$storage->attach($arg);
				$h = spl_object_hash($arg);
			}
			else{
				$h = sha1($arg);
			}
			$hash[] = sha1($k).'='.$h;
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