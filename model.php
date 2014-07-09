<?php namespace surikat;
use surikat\control\str;
use surikat\model\R;
use surikat\model\Query;
use surikat\model\Query4D;
use surikat\model\RedBeanPHP\OODBBean;
class model {
	static function __callStatic($func,$args){
		$q = new Query4D(array_shift($args));
		return call_user_func_array(array($q,$func),$args);
	}
	static function schemaAuto($table,$force=false){
		if(!control::devHas(control::dev_model)&&!$force)
			return;
		$path = 'model/schema.'.$table.'.php';
		if(is_file($path)&&!R::getWriter()->tableExists($table)&&is_array($a=include($path)))
			R::storeMultiArray($a);
	}
	static function load($table,$id,$flag=0){
		if(is_integer($id))
			$w = 'id';
		else{
			$w = 'label';
			if($flag){
				if($flag&Query::FLAG_ACCENT_INSENSITIVE){
					$w = 'uaccent('.$w.')';
					$id = str::unaccent($id);
				}
				if($flag&Query::FLAG_CASE_INSENSITIVE){
					$w = 'LOWER('.$w.')';
					$id = str::tolower($id);
				}
			}
		}
		return R::findOne($table,'WHERE '.$w.'=?',array($id));
	}
	static function getModelClass($type){
		return class_exists($c='\\model\\Table_'.ucfirst($type))?$c:'\\model\\Table';
	}
	static function getClassModel($c){
		return lcfirst(ltrim(substr(ltrim($c,'\\'),11),'_'));
	}
	static function getTableColumnDef($t,$col,$key=null){
		$c = self::getClassModel($t);
		return $c::getColumnDef($col,$key);
	}
}