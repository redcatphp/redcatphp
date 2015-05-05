<?php namespace Database;
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
use ObjexLoader\MutatorCallTrait;
use Database\RedBeanPHP\OODBBean;
use Database\RedBeanPHP\SimpleModel;
use Database\RedBeanPHP\QueryWriter\AQueryWriter;
use Vars\JSON;
use BadMethodCallException;
use Database\ValidationException; //for allowing mirrored exception class catching and (optional) hook
class Model extends SimpleModel implements \ArrayAccess,\IteratorAggregate{
	use MutatorCallTrait;
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
	private static $__binded = [];
	protected static $loadUniq = 'uniq';
	protected static $loadUniqs = [];
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
	static $metaCast = [];
	protected $sync;
	private static $_checkUniq;
	private $errors = [];
	private $_relationsKeysStore = [];
	protected $table;
	protected $type;
	protected $bean;
	protected $creating;
	protected $checkUniq = true;
	protected $_on = [];
	protected $_onces = [];
	protected $queryWriter;
	protected $dirSync = '.tmp/sync/';
	static function _checkUniq($b=null){
		self::$_checkUniq = isset($b)?!!$b:true;
	}
	function checkUniq($b=null){
		$this->checkUniq = isset($b)?!!$b:true;
	}
	function _binder($table){
		if(!in_array($table,self::$__binded)){
			$c = $this->Database->getModelClass($table);
			foreach($c::getDefColumns('readCol') as $col=>$func)
				$this->Database->bindFunc('read', $table.'.'.$col, $func);
			foreach($c::getDefColumns('writeCol') as $col=>$func)
				$this->Database->bindFunc('write', $table.'.'.$col, $func);
			self::$__binded[] = $table;
		}
	}
	private $Database;
	function __construct($table,$db){
		$this->Database = $db;
		$this->queryWriter = $this->Database->getWriter();
		$this->table = $table;
		$this->type = $this->queryWriter->reverseCase($table);
		$this->_binder($table);
	}
	function getKeys(){
		return array_keys($this->getProperties());
	}
	function getArray(){
		$a = [];
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
		if(func_num_args()>2&&func_get_arg(2))
			$this->throwValidationError($this->getErrors());
		return $this;
	}
	function throwValidationError($e=null){
		throw new ValidationException('Données manquantes ou erronées',$e);
	}
	function _relationsKeysRestore(){
		foreach($this->_relationsKeysStore as $k=>$v)
			$this->$k = $v;
	}
	function _relationsKeysStore(){
		$r = [];
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
		$this->type = $this->queryWriter->reverseCase($this->table);
		$c = get_class($this);
		foreach($c::getDefColumns('cast') as $k=>$cast)
			$this->bean->setMeta('cast.'.$k,$cast);
		$this->trigger('new');
	}
	function on($f,$c){
		if(!isset($this->_on[$f]))
			$this->_on[$f] = [];
		$this->_on[$f][] = $c;
	}
	function triggerRelated($f,$asc=true){
		foreach($this as $o){
			if(is_object($o)&&!isset($o->_onces[$f])){
				$o->_onces[$f] = true;
				$o->trigger($asc,$f);
			}
			elseif(is_array($o)){
				foreach($o as $_o){
					if(is_object($_o)&&!isset($_o->_onces[$f])){
						$_o->_onces[$f] = true;
						$_o->trigger($asc,$f);
					}
				}
			}
		}
	}
	function trigger(){
		$args = func_get_args();
		$f = array_shift($args);
		$propag = false;
		$asc = false;
		if(is_bool($f)){
			$propag = true;
			$asc = $f;
			$f = array_shift($args);
		}
		if($propag&&$asc){
			$this->triggerRelated($f,$asc);
		}
		$c = 'on'.ucfirst($f);
		if(method_exists($this,$c)){
			call_user_func_array([$this,$c],$args);
		}
		if(isset($this->_on[$f])){
			foreach($this->_on[$f] as $c){
				call_user_func($c,$this,$args);
			}
		}
		if($propag&&!$asc){
			$this->triggerRelated($f,$asc);
		}
	}
	function open(){
		$this->creating = false;
		$this->table = $this->getMeta('type');
		$this->type = $this->queryWriter->reverseCase($this->table);
		$this->trigger('read');
	}
	
	function xown($key){
		$k = 'xown'.ucfirst($key);
		return $this->bean->$k;
	}
	function own($key){
		$k = 'own'.ucfirst($key);
		return $this->bean->$k;
	}
	function shared($key){
		$k = 'shared'.ucfirst($key);
		return $this->bean->$k;
	}
	function trash(){
		return $this->Database->trash($this);
	}
	function store(){
		return $this->Database->store($this);
	}
	function storing(){
		$this->_filterConvention();
		$this->_rulerConvention();
		$this->_indexConvention();
		$this->trigger(true,'validate');
		$e = $this->getErrors();
		if($e){
			$this->throwValidationError($e);
		}
		else{
			return true;
		}
	}
	
	function update(){
		$this->_relationsKeysStore();
		$this->trigger('change');
		if($this->creating)
			$this->trigger('create');
		else
			$this->trigger('update');
	}
	function after_update(){
		$this->_relationsKeysRestore();
		if(empty($this->errors)){
			if($this->creating)
				$this->trigger('created');
			else
				$this->trigger('updated');
			if($this->sync)
				$this->sync('model.'.$this->table);
		}
		$this->trigger('changed');
		if(isset($this->_onces['validate']))
			unset($this->_onces['validate']);

	}
	function _filterConvention(){
		foreach(static::getDefColumns('filter') as $col=>$call){
			foreach((array)$call as $f=>$a){
				if(is_integer($f)){
					$f = $a;
					$a = [];
				}
				if(!is_array($a))
					$a = (array)$a;
				array_unshift($a,$this->$col);
				$this->$col = call_user_func_array(['RedBoxORM\Validation\Filter',$f],$a);
			}
		}
	}
	function _rulerConvention(){
		foreach(static::getDefColumns('ruler') as $col=>$call){
			foreach((array)$call as $f=>$a){
				if(is_integer($f)){
					$f = $a;
					$a = [];
				}
				if(!is_array($a))
					$a = (array)$a;
				array_unshift($a,$this->$col);
				if(!call_user_func_array(['RedBoxORM\Validation\Ruler',$f],$a))
					$this->error($col,'ruler '.$f.' with value '.array_shift($a).' and with params "'.implode('","',$a).'"');
			}
		}
	}
	function _uniqConvention(){
		$uniqs = [];
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
			if(	$this->checkUniq
				&&self::$_checkUniq!==false
				&&($r=$this->Database->findOne($this->table,implode(' = ? OR ',array_keys($uniqs)).' = ? ', array_values($uniqs)))
				&&$r->id!=$this->bean->id
			){
				$throwed = false;
				foreach($uniqs as $k=>$v){
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
			if(!empty($uniqs)&&!$this->Database->getRedBean()->isFrozen())
				$this->queryWriter->addUniqueIndex($this->table,array_keys($uniqs));
		}
	}
	function _indexConvention(){
		if($this->getMeta('tainted'))
			$this->_uniqConvention();
		$this->_fulltextConvention();
	}
	function _fulltextConvention(){
		$w = &$this->queryWriter;
		$t = $this->getTable();
		foreach(static::getDefColumns('fulltext') as $col=>$cols){
			$lang = static::getColumnDef($col,'fulltextLanguage');
			$this->on('created',function($entry)use($t,&$w,$col,$cols,$lang){
				if(!in_array($col,array_keys($this->Database->inspect($t)))){
					$w->addColumnFulltext($t, $col);
					$w->buildColumnFulltext($t, $col, $cols, $lang);
					$w->addIndexFullText($t, $col, null , $lang);
				}
				$w->adapter->exec($w->buildColumnFulltextSQL($t,$col,$cols,$lang).' WHERE id=?',[$entry->id]);
			});
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
	function __call($func, array $args=[]){
		if(is_callable([$this->bean,$func]))
			return call_user_func_array([$this->bean,$func],$args);
		else
			throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()',get_class($this),$func));
	}
	function xownList($type){
		return $this->__get('xown'.ucfirst($this->table).'_'.$type);
	}
	function ownList($type){
		return $this->__get('own'.ucfirst($this->table).'_'.$type);
	}

	function arraySetter($k, $v){
		if(isset($v['id'])&&$id=$v['id']){
			$val = $this->Database->load($k,$id);
			foreach($v as $_k=>$_v)
				if($k!='id')
					$v->$_k = $_v;
		}
		elseif(isset($v[static::$loadUniq])&&($id=$v[static::$loadUniq])){
			if(!($val = $this->Database->load($k,$id)))
				$val = $this->Database->newOne($k,$v);
			foreach($v as $_k=>$_v)
				if($k!=static::$loadUniq)
					$v->$_k = $_v;
		}
		else
			$val = $this->Database->newOne($this->type.ucfirst($k),$v);
		return [$k,$val];
	}
	function arraysSetter($k, $v){
		$uk = ucfirst($k);
		if(isset($v['id'])&&$id=$v['id']){
			$v = $this->Database->load($k,$id);
			$k = 'shared'.$uk;
		}
		elseif(isset($v[static::$loadUniq])&&($id=$v[static::$loadUniq])){
			if(!($v = $this->Database->load($k,$id)))
				$v = $this->Database->newOne($k,$v);
			$k = 'shared'.$uk;
		}
		else{
			$v = $this->Database->newOne($this->type.$uk,$v);
			$k = 'xown'.ucfirst($this->type).$uk;
		}
		return [$k,$v];
	}
	function _keyMapperConvention($k){
		$uk = ucfirst($k);
		if(isset(static::${'column'.$uk.'Link'})){
			if(static::${$col}===true)
				$k = 'link'.ucfirst($k);
			else
				$k = 'link'.ucfirst(static::${'column'.$uk.'Link'});
		}
		if(isset(static::${'column'.$uk.'Is'}))
			$k = (static::${'column'.$uk.'Is'}).ucfirst($k);
		return $k;
	}
	function &__get($k){
		$k = $this->_keyMapperConvention($k);
		if(	(strpos($k,'own')===0&&ctype_upper(substr($k,3,1)))
			||(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1)))
			||(strpos($k,'shared')===0&&ctype_upper(substr($k,6,1)))
		)
			return $this->bean->__get($k);
		if(method_exists($this,$method = '_get'.ucfirst($k)))
			return $this->$method($v);
		return $this->bean->__get($k);
	}
	function __set($k,$v){
		$k = $this->_keyMapperConvention($k);
		if(	(strpos($k,'own')===0&&ctype_upper(substr($k,3,1)))
			||(strpos($k,'xown')===0&&ctype_upper(substr($k,4,1)))
			||(strpos($k,'shared')===0&&ctype_upper(substr($k,6,1)))
		)
			return $this->bean->__set($k,$v);
		if(method_exists($this,$method = '_set'.ucfirst($k)))
			return $this->$method($v);
		elseif(is_array($v)){
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
		$props = $this->bean->getProperties();
		foreach($props as $k=>$v){
			if(is_object($v)){
				$props[$k] = $v->box();
			}
			elseif(is_array($v)){
				foreach($v as $_k=>$_v){
					if(is_object($_v)){
						$props[$k][$_k] = $_v->box();
					}
				}
			}
		}
        return new\ArrayIterator( $props );
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
    function getType(){
		return $this->type;
	}
	static function getDefColumns($key=null){
		$key = ucfirst($key);
		$a = [];
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
	function setDirSync($d){
		$this->dirSync = rtrim($d,'/').'/';
	}
	function sync($sync){
		$syncF = $this->dirSync.$sync.'.sync';
		if(!is_file($syncF)){
			@mkdir(dirname($syncF),0777,true);
			file_put_contents($syncF,'');
		}
		else
			touch($syncF);
	}
}