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
use surikat\control\sync;
use surikat\model\R;
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\SimpleModel;
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
	private $errors = array();
	private $relationsKeysStore = array();
	static $metaCast = array();
	static $sync = true;
	protected $table;
	protected $bean;
	protected $creating;
	protected $__on = array();
	var $breakValidationOnError;
	function __construct(){
		if(func_num_args())
			$this->table = func_get_arg(0);
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
				foreach($o as $_o){
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
		foreach($c::$metaCast as $k=>$cast)
			$this->bean->setMeta('cast.'.$k,$cast);
		//R::bindFunc('write',
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
		//R::bindFunc('read',
		$this->creating = false;
		$this->table = $this->getMeta('type');
		$this->trigger('read');
	}
	function update(){
		$r = array();
		foreach(array_keys($this->getProperties()) as $k)
			if(strpos($k,'own')===0&&ctype_upper(substr($k,3,1)))
				$r[] = $k;
			elseif(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1)))
				$r[] = $k;
			elseif(strpos($k,'shared')===0&&ctype_upper(substr($k,6,1)))
				$r[] = $k;
		$this->relationsKeysStore($r);
		
		$this->errors = array();
		$this->trigger('validate');
		$e = $this->getErrors();
		if($e&&$this->breakValidationOnError)
			throw new Exception_Validation('Données manquantes ou erronées',$e);
		if(!$e){
			if($this->creating)
				$this->trigger('create');
			else
				$this->trigger('update');
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
	function loadBean( OODBBean $bean ){
		$this->bean = $bean;
	}
	function __call($func,array $args=array()){
		if(is_callable(array($this->bean,$func)))
			return call_user_func_array(array($this->bean,$func),$args);
		else
			throw new \BadMethodCallException('Class "'.get_class($this).'": call to undefined method '.$func);
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
}
