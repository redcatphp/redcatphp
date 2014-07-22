<?php namespace surikat\model;
use surikat\model;
use surikat\control;
use surikat\control\Config;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
class R extends RedBeanPHP\Facade{
	static function initialize(){
		extract(Config::model());
		if(!isset($frozen))
			$frozen = !control::devHas(control::dev_model);
		$port = isset($port)&&$port?';port='.$port:'';
		self::setup("$type:host=$host$port;dbname=$name",$user,$password,$frozen);
		if(control::devHas(control::dev_model_redbean))
			R::debug();
	}
	function __call($f,$args){
		if(substr($f,-4)=='Call'&&method_exists($method=substr($f,0,-4)))
			return call_user_func_array(array($this,$method),array(array_shift($args),$args));
		return call_user_func_array(array('parent',$f),$args);
	}
	static function loadRow($type,$sql,$binds=array()){
		$b = R::convertToBeans($type,array(self::getRow($type,$sql,$binds)));
		return $b[0];
	}
	static function getRowX(){
		$a = func_get_args();
		$sql = array_shift($a);
		
	}
	static function findOrNewOne($type,$params=array(),$insert=null){
		$query = array();
		$bind = array();
		foreach($params as $k=>$v){
			$query[] = $k.'=?';
			$bind[] = $v;
		}
		$query = implode(' AND ',$query);
		$type = (array)$type;
		foreach($type as $t)
			if($bean = R::findOne($t,$query,$bind))
				break;
		if(!$bean){
			if(is_array($insert))
				$params = array_merge($params,$insert);
			$bean = R::newOne(array_pop($type),$params);
		}
		return $bean;
	}
	static function newOne($type,$params=array()){
		$bean = self::dispense($type);
		if(is_string($params))
			$params = array('label'=>$params);
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	static function queryWrapArg(&$query,$wrap,$arg){
		$x = explode('?',$query);
		$s = '';
		for($i=0;$i<count($x)-1;$i++)
			$s .= $x[$i].($arg===$i?$wrap:'?');
		$s .= array_pop($x);
		$query = $s;
		return $query;
	}
	static function storeMultiArray(array $a){
		foreach($a as $v)
			self::storeArray($v);
	}
	static function storeArray(array $a){
		$dataO = self::dispense($a['type']);
		foreach($a as $k=>$v){
			if($k=='type')
				continue;
			if(stripos($k,'own')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,3));
					$own = self::dispense($type);
					foreach((array)$v2 as $k3=>$v3)
						if($k3!='type')
							$own->$k3 = $v3;
					$dataO->{$k}[] = $own;
				}
			}
			elseif(stripos($k,'shared')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,6));
					if(!is_integer(filter_var($v2, FILTER_VALIDATE_INT)))
						$v2 = self::__callStatic('cell',array($type,array('select'=>'id','where'=>'label=?'),array($v2)));
					if($v2)
						$dataO->{$k}[] = self::load($type,$v2);
				}
			}
			else
				$dataO->$k = $v;
		}
		return self::store($dataO);
	}

	static function schemaAuto($table,$force=false){
		if(!control::devHas(control::dev_model)&&!$force)
			return;
		$path = 'model/schema.'.$table.'.php';
		if(is_file($path)&&!R::getWriter()->tableExists($table)&&is_array($a=include($path)))
			R::storeMultiArray($a);
	}
	static function getAll4D(){
		return Query::explodeAggTable(call_user_func_array(array('self','getAll'),func_get_args()));
	}
	static function getRow4D(){
		return Query::explodeAgg(call_user_func_array(array('self','getRow'),func_get_args()));
	}
	static function __callStatic($f,$args){
		if(strpos($f,'loadUniq')===0&&ctype_upper(substr($f,8,1)))
			return self::loadUniq(array_shift($args),array_shift($args),lcfirst(substr($f,8)));
		return parent::__callStatic($f,$args);
	}
	static function load($type,$id){
		if(is_string($id)||func_num_args()>2)
			return self::loadUniq($type,$id,func_get_arg(2));
		return parent::load($type,$id);
	}
	static function loadUniq($table,$id,$column=null){
		$c = R::getModelClass($table);
		if(is_array($table)){
			foreach($table as $tb)
				if($r = self::loadUniq($tb,$id,$column))
					return $r;
		}
		else{
			if(!$column)
				$column = $c::getLoaderUniq($column);
			if(is_array($column)){
				foreach($column as $col)
					if($r = self::loadUniq($table,$id,$col))
						return $r;
			}
			else{
				return R::findOne($table,'WHERE '.$column.'=?',array($c::loadUniqFilter($id)));
			}
		}
	}
	static function getModelClass($type){
		return class_exists($c='\\model\\Table_'.ucfirst($type))?$c:'\\model\\Table';
	}
	static function getClassModel($c){
		return lcfirst(ltrim(substr(ltrim($c,'\\'),11),'_'));
	}
	static function getTableColumnDef($t,$col,$key=null){
		$c = self::getModelClass($t);
		return $c::getColumnDef($col,$key);
	}
}
R::initialize();