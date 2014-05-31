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
use surikat\model\R;
use surikat\model\RedBean\OODBBean;
class Table implements \ArrayAccess,\IteratorAggregate{
	#<workflow CRUD>
	//function onNew(){}
	//function onCreate(){}
	//function onCreated(){}
	//function onRead(){}
	//function onUpdate(){}
	//function onUpdated(){}
	//function onValidate(){}
	//function onDelete(){}
	//function onDeleted(){}
	#</workflow>
	protected $table;
	protected $bean;
	protected $creating;
	protected $errors = array();
	protected $__on = array();
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
	function dispense(){
		$this->creating = true;
		$this->table = $this->getMeta('type');
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
		$this->trigger('validate');
		if($this->creating)
			$this->trigger('create');
		else
			$this->trigger('update');
		if(!empty($this->errors))
			throw new Exception_Validation('Données manquantes ou erronées',$this->errors);
	}
	function after_update(){
		if($this->creating)
			$this->trigger('create');
		else
			$this->trigger('update');
	}
	function delete(){
		$this->trigger('delete');
	}
	function after_delete(){
		$this->trigger('deleted');
	}
	public function loadBean( OODBBean $bean ){
		$this->bean = $bean;
	}
	public function __call($func,array $args=array()){
		if(is_callable(array($this->bean,$func)))
			return call_user_func_array(array($this->bean,$func),$args);
		else
			throw new \BadMethodCallException('Class "'.get_class($this).'": call to undefined method '.$func);
	}
	public function __get($prop){
		return $this->bean->$prop;
	}
	public function __set( $prop, $value ){
		$this->bean->$prop = $value;
	}
	public function __isset( $key ){
		return isset( $this->bean->$key );
	}
	public function __unset( $key ){
		unset( $this->bean->$key );
	}
	public function box(){
		return $this;
	}
	public function unbox(){
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
