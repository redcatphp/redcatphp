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
onChange
onUpdated					$model->after_update()
onChanged

onDelete	R::trash		$model->delete()		DELETE		DELETE	DELETE
onDeleted	R::trash		$model->after_delete()	DELETE		DELETE	DELETE

*/
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\SimpleModel;
use surikat\control;
use surikat\control\JSON;
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
	function onChange(){}
	function onChanged(){}
	function onDelete(){}
	function onDeleted(){}
	#</workflow>
	private static $__binded = array();
	protected static $loadUniq = 'name';
	protected static $loadUniqs = array();
	static function getLoadUniq(){
		return static::$loadUniq;
	}
	static function getLoaderUniq(){
		$l = static::$loadUniq;
		if($f=static::getColumnDef($l,'readCol'))
			$l = $f.'('.$l.')';
		return $l;
	}
	static function loadUniqFilter($str){
		return $str;
	}
	static $metaCast = array();
	static $sync;
	private $errors = array();
	private $_relationsKeysStore = array();
	protected $table;
	protected $type;
	protected $bean;
	protected $creating;
	protected $checkUniq = true;
	protected $__on = array();
	protected $breakValidationOnError;
	function checkUniq($b=null){
		$this->checkUniq = isset($b)?!!$b:true;
	}
	function breakOnError($b=null){
		$this->breakValidationOnError = isset($b)?!!$b:true;
	}
	static function _binder($table){
		if(!in_array($table,self::$__binded)){
			$c = R::getModelClass($table);
			foreach($c::getDefColumns('readCol') as $col=>$func)
				R::bindFunc('read', $table.'.'.$col, $func);
			foreach($c::getDefColumns('writeCol') as $col=>$func)
				R::bindFunc('write', $table.'.'.$col, $func);
			self::$__binded[] = $table;
		}
	}
	function __construct($table){
		$this->table = $table;
		$this->type = R::toCamel($table);
		self::_binder($table);
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
	function _relationsKeysRestore(){
		foreach($this->_relationsKeysStore as $k=>$v)
			$this->$k = $v;
	}
	function _relationsKeysStore(){
		$r = array();
		foreach($this->getKeys() as $k)
			if(strpos($k,'own')===0&&ctype_upper(substr($k,3,1)))
				$r[] = $k;
			elseif(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1)))
				$r[] = $k;
			elseif(strpos($k,'shared')===0&&ctype_upper(substr($k,6,1)))
				$r[] = $k;
		$this->_relationsKeysStore = $r;
	}
	function getErrors($d=0){
		$e = $this->errors;
		foreach($this->bean as $k=>$o){
			if($d!=1&&is_array($o)){
				foreach($o as $i=>$_o){
					if(!is_object($_o)){
						unset($o[$i]);
						continue;
					}
					$_e = $_o->getErrors(2);
					if(!empty($_e))
						$e[$k] = $_e;
				}
			}
			elseif($d<2&&$o instanceof OODBBean){
				$_e = $o->getErrors(1);
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
		$this->type = R::toCamel($this->table);
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
		$this->type = R::toCamel($this->table);
		$this->trigger('read');
	}
	function update(){
		$this->_relationsKeysStore();
		$this->_filterConvention();
		$this->_rulerConvention();
		$this->_indexConvention();

		$this->trigger('validate');
		$e = $this->getErrors();
		if($e&&$this->breakValidationOnError){
			if(control::devHas(control::dev_model))
				print_r($e);
			throw new Exception_Validation('Données manquantes ou erronées',$e);
		}
			
		if(!$e){
			$this->trigger('change');
			if($this->creating)
				$this->trigger('create');
			else
				$this->trigger('update');
		}
	}
	function _filterConvention(){
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
	}
	function _rulerConvention(){
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
	}
	function _uniqConvention(){
		$uniqs = array();
		foreach($this->getKeys() as $key){
			if(	$key==static::$loadUniq
				||strpos($key,'uniq_')===0
				||in_array($key,static::$loadUniqs)
			)
				$uniqs[$key] = $this->bean->$key;
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
			if(isset($uniqs[static::$loadUniq]))
				unset($uniqs[static::$loadUniq]);
			if(!empty($uniqs)&&!R::getRedBean()->isFrozen())
				//$this->bean->setMeta('buildcommand.unique',array(array_keys($uniqs)));
				R::getWriter()->addUniqueIndex($this->table,array_keys($uniqs));
		}
	}
	function _indexConvention(){
		$this->_uniqConvention();
		$this->_fulltextConvention();
	}
	function _fulltextConvention(){
		$w = R::getWriter();
		foreach(static::getDefColumns('fulltext') as $col=>$cols){
			if(!in_array($col,array_keys(R::inspect($this->table)))){
				$w->addColumnFulltext($this->table, $col);
				$w->addIndexFullText($this->table, $col);
				$w->handleFullText($this->table, $col, $cols, $this);
			}
		}
	}
	function after_update(){
		$this->_relationsKeysRestore();
		if(empty($this->errors)){
			if($this->creating)
				$this->trigger('created');
			else
				$this->trigger('updated');
			if(static::$sync)
				sync::update('model.'.$this->table);
		}
		$this->trigger('changed');
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
	function xownList($type){
		return $this->__get('xown'.ucfirst($this->table).'_'.$type);
	}
	function ownList($type){
		return $this->__get('own'.ucfirst($this->table).'_'.$type);
	}
	function &__get($prop){
		$ref = &$this->bean->$prop;
		return $ref;
	}
	function arraySetter($k, $v){
		if(isset($v['id'])&&$id=$v['id']){
			$val = R::load($k,$id);
			foreach($v as $_k=>$_v)
				if($k!='id')
					$v->$_k = $_v;
		}
		elseif(isset($v[static::$loadUniq])&&($id=$v[static::$loadUniq])){
			if(!($val = R::load($k,$id)))
				$val = R::newOne($k,$v);
			foreach($v as $_k=>$_v)
				if($k!=static::$loadUniq)
					$v->$_k = $_v;
		}
		else
			$val = R::newOne($this->type.ucfirst($k),$v);
		return array($k,$val);
	}
	function arraysSetter($k, $v){
		$uk = ucfirst($k);
		if(isset($v['id'])&&$id=$v['id']){
			$v = R::load($k,$id);
			$k = 'shared'.$uk;
		}
		elseif(isset($v[static::$loadUniq])&&($id=$v[static::$loadUniq])){
			if(!($v = R::load($k,$id)))
				$v = R::newOne($k,$v);
			$k = 'shared'.$uk;
		}
		else{
			$v = R::newOne($this->type.$uk,$v);
			$k = 'xown'.ucfirst($this->type).$uk;
		}
		return array($k,$v);
	}
	function __set( $k, $v ){
		if(method_exists($this,$method = '_set'.ucfirst($k)))
			return $this->$method($v);
		if(is_array($v)){
			if(is_integer(key($v))){
				foreach($v as &$_v)
					list($key,$_v) = $this->arraysSetter($k,$_v);
				$k = $key;
			}
			else
				list($k,$v) = $this->arraySetter($k,$v);
			if(!$v)
				return;
		}
		$this->bean->__set($k,$v);
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
		if($key)
			$p .= ucfirst($key);
		if(property_exists($c,$p))
			return $c::$$p;
	}
	function __toString(){
		return "\n".json_encode($this->getArray(),
			JSON_UNESCAPED_UNICODE
			|JSON_PRETTY_PRINT
			|JSON_NUMERIC_CHECK
			|JSON_UNESCAPED_SLASHES
		);
	}
}