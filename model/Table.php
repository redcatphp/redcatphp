<?php namespace surikat\model;
/*

Surikat		RedBean			Model FUSE				 CRUD		HTTP	SQL

onNew		R::dispense		$model->dispense() 		CREATE		POST	INSERT
onCreate	R::store		$model->update()
onValidate		
onCreated					$model->after_update()

onRead		R::load			$model->open() 			READ		GET		SELECT

onUpdate	R::store		$model->update()		UPDATE		PUT		UPDATE
onValidate
onUpdated					$model->after_update()

onDelete	R::trash		$model->delete()		DELETE		DELETE	DELETE
onDeleted	R::trash		$model->after_delete()	DELETE		DELETE	DELETE

*/
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\SimpleModel;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
use surikat\control;
use surikat\control\sync;
use BadMethodCallException;
class Table extends SimpleModel implements \ArrayAccess,\IteratorAggregate{
	#<workflow CRUD>
	function onNew(){}
	function onCreate(){}
	function onCreated(){}
	function onRead(){}
	function onUpdate(){}
	function onUpdated(){}
	function onValidate(){}
	function onDelete(){}
	function onDeleted(){}
	#</workflow>
	private static $__binded = array();
	protected static $loadUniq = 'name';
	static function getLoaderUniq(){
		return static::$loadUniq;
	}
	static function loadUniqFilter($str){
		return $str;
	}
	static $metaCast = array();
	static $sync = true;
	private $errors = array();
	private $relationsKeysStore = array();
	protected $table;
	protected $bean;
	protected $creating;
	protected $checkUniq = true;
	protected $__on = array();
	protected $breakValidationOnError;
	function breakOnError($b=null){
		$this->breakValidationOnError = isset($b)?!!$b:true;
	}
	function __construct($table){
		$this->table = $table;
		$c = get_called_class();
		if(!in_array($c,self::$__binded)){
			foreach(static::getDefColumns('read') as $col=>$func)
				foreach((array)$func as $f)
					R::bindFunc($func, $table.'.'.$col, $f);
			foreach(static::getDefColumns('write') as $col=>$func)
				foreach((array)$func as $f)
					R::bindFunc($func, $table.'.'.$col, $f);
			self::$__binded[] = $c;
		}
	}
	function getKeys(){
		return array_keys($this->getProperties());
	}
	function getArray(){
		$a = array();
		foreach($this->bean as $k=>$v)
			if(is_array($v))
				foreach($v as $_k=>$_v)
					$a[$k][$_k] = $_v instanceof OODBBean?$_v->getArray():$_v;
			else
				$a[$k] = $v instanceof OODBBean?$v->getArray():$v;
		return $a;
	}
	function error($k){
		if(func_num_args()>1)
			$this->errors[$k] = func_get_arg(1);
		else
			$this->errors[] = $k;
	}
	function relationsKeysRestore(){
		foreach($this->relationsKeysStore as $k=>$v)
			$this->$k = $v;
		
	}
	function relationsKeysStore($keys){
		$r = array();
		foreach($keys as $k)
			$r[$k] = $this->bean->$k;
		$this->relationsKeysStore = $r;
	}
	function getErrors(){
		$e = $this->errors;
		foreach($this->bean as $k=>$o){
			if(is_array($o)){
				foreach($o as $i=>$_o){
					if(!is_object($_o)){
						unset($o[$i]);
						continue;
					}
					$_e = $_o->getErrors();
					if(!empty($_e))
						$e[$k] = $_e;
				}
			}
			elseif($o instanceof OODBBean){
				$_e = $o->getErrors();
				if(!empty($_e))
					$e[$k] = $_e;
			}
		}
		$this->errors = $e;
		return !empty($e)?$e:null;
	}
	function dispense(){
		$this->creating = true;
		$this->table = $this->getMeta('type');
		$c = get_class($this);
		foreach($this->getKeys() as $k)
			if($cast=$c::getColumnDef($k,'cast'))
				$this->bean->setMeta('cast.'.$k,$cast);
		$this->trigger('new');
	}
	function on($f,$c){
		if(!isset($this->__on[$f]))
			$this->__on[$f] = array();
		$this->__on[$f][] = $c;
	}
	function trigger(){
		$args = func_get_args();
		$f = array_shift($args);
		$c = 'on'.ucfirst($f);
		if(method_exists($this,$c))
			call_user_func_array(array($this,$c),$args);
		if(isset($this->__on[$f]))
			foreach($this->__on[$f] as $c)
				call_user_func($c,$this,$args);
	}
	function open(){
		$this->creating = false;
		$this->table = $this->getMeta('type');
		$this->trigger('read');
	}
	function update(){
		$r = array();
		foreach($this->getKeys() as $k)
			if(strpos($k,'own')===0&&ctype_upper(substr($k,3,1)))
				$r[] = $k;
			elseif(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1)))
				$r[] = $k;
			elseif(strpos($k,'shared')===0&&ctype_upper(substr($k,6,1)))
				$r[] = $k;
		$this->relationsKeysStore($r);

		foreach(static::getDefColumns('filter') as $col=>$call){
			foreach((array)$call as $f=>$a){
				if(is_integer($f)){
					$f = $a;
					$a = array();
				}
				if(!is_array($a))
					$a = (array)$a;
				array_unshift($a,$this->$col);
				$this->$col = call_user_func_array(array('control\\filter',$f),$a);
			}
		}
		foreach(static::getDefColumns('ruler') as $col=>$call){
			foreach((array)$call as $f=>$a){
				if(is_integer($f)){
					$f = $a;
					$a = array();
				}
				if(!is_array($a))
					$a = (array)$a;
				array_unshift($a,$this->$col);
				if(!call_user_func_array(array('control\\ruler',$f),$a))
					$this->error($col,'ruler '.$f.' with value '.array_shift($a).' and with params "'.implode('","',$a).'"');
			}
		}
		$this->_uniqConvention();

		$this->trigger('validate');
		$e = $this->getErrors();
		if($e&&$this->breakValidationOnError){
			if(control::devHas(control::dev_model))
				print_r($e);
			throw new Exception_Validation('Données manquantes ou erronées',$e);
		}
			
		if(!$e){
			if($this->creating)
				$this->trigger('create');
			else
				$this->trigger('update');
		}
	}
	static function _keyExplode($k){
		return AQueryWriter::camelsSnake($k);
	}
	static function _keyImplode($k){
		$x = explode('_',$k);
		foreach($x as &$_x)
			$_x = ucfirst($_x);
		return implode('',$x);
	}
	function _uniqConvention(){
		$uniqs = array();
		foreach($this->getKeys() as $key){
			if($key==static::$loadUniq)
				$uniqs[$key] = $this->bean->$key;
			elseif(strpos($key,'uniq_')===0){
				$k = substr($key,5);
				$bk = self::_keyImplode($k);
				$uniqs[$k] = $this->bean->$bk = $this->bean->$key;
				unset($this->bean->$key);
			}
		}
		foreach(array_keys(static::getDefColumns('uniq')) as $col)
			$uniqs[$col] = $this->bean->$col;
		if(!empty($uniqs)){
			if($this->checkUniq&&($r=R::findOne($this->table,implode(' = ? OR ',array_keys($uniqs)).' = ? ', array_values($uniqs)))){
				$throwed = false;
				foreach($uniqs as $k=>$v){
					$bk = self::_keyImplode($k);
					if($v==$r->$k){
						$this->error($k,'not uniq on column "'.$k.'" with value "'.$v.'"');
						$throwed = true;
					}
				}
				if(!$throwed)
					$this->error('Unicity constraint violation');
			}
			$this->bean->setMeta("buildcommand.unique" , array(array_keys($uniqs)));
		}
	}
	function after_update(){
		$this->relationsKeysRestore();
		if(empty($this->errors)){
			if($this->creating)
				$this->trigger('created');
			else
				$this->trigger('updated');
			if(static::$sync)
				sync::update('model.'.$this->table);
		}
	}
	function delete(){
		$this->trigger('delete');
	}
	function after_delete(){
		$this->trigger('deleted');
	}
	function loadBean(OODBBean $bean){
		$this->bean = $bean;
	}
	function __call($func,array $args=array()){
		if(is_callable(array($this->bean,$func)))
			return call_user_func_array(array($this->bean,$func),$args);
		else
			throw new BadMethodCallException('Class "'.get_class($this).'": call to undefined method '.$func);
	}
	function __get($prop){
		return $this->bean->$prop;
	}
	function __set( $prop, $value ){
		$this->bean->$prop = $value;
	}
	function __isset( $key ){
		return isset( $this->bean->$key );
	}
	function __unset( $key ){
		unset( $this->bean->$key );
	}
	function box(){
		return $this;
	}
	function unbox(){
		return $this->bean;
	}
	function getIterator(){
        return $this->bean->getIterator();
	}
	function offsetSet($offset,$value){
        return $this->bean->offsetSet($offset,$value);
    }
    function offsetExists($offset) {
        return $this->bean->offsetExists($offset);
    }
    function offsetUnset($offset) {
        return $this->bean->offsetUnset($offset);
    }
    function offsetGet($offset) {
        return $this->bean->offsetGet($offset);
    }
    function getTable(){
		return $this->table;
	}
	static function getDefColumns($key=null){
		$key = ucfirst($key);
		$a = array();
		$lk = strlen($key);
		foreach(get_class_vars(get_called_class()) as $k=>$v)
			if(strpos($k,'column')===0&&ctype_upper(substr($k,6,1))&&($p=strrpos($k,$key)===strlen($k)-$lk)&&($k=lcfirst(substr($k,6,-1*$lk))))
				$a[$k] = $v;
		return $a;
	}
	static function getColumnDef($col,$key=null){
		$c = get_called_class();
		$p = 'column'.ucfirst($col);
		if(property_exists($c,$p))
			$r = $c::$$p;
		if($key!==null)
			return isset($r[$key])?$r[$key]:null;
		else
			return $r;
	}
}