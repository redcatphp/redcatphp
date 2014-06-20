<?php namespace surikat;
use surikat\control\str;
use surikat\model\R;
use surikat\model\Compo;
use surikat\model\RedBeanPHP\OODBBean;
class model {
	private static $AVAILABLE;
	const FLAG_ACCENT_INSENSITIVE = 2;
	const FLAG_CASE_INSENSITIVE = 4;
	static function available($force=null){
		if(self::$AVAILABLE===null||$force){
			self::$AVAILABLE = true;
			try{
				R::getCell('SELECT database()');
			}
			catch(\PDOException $e){
				self::$AVAILABLE = false;
			}
		}
		return self::$AVAILABLE;
	}
	static function storeArray(){
		return call_user_func_array(array('surikat\model\R',__FUNCTION__),func_get_args());
	}
	static function __callStatic($func,$args){
		return call_user_func_array(array('surikat\model\Compo',$func),$args);
	}
	static function tableExist($table){
		return in_array($table,Compo::listOfTables());
	}
	static function schemaAuto($table,$force=false){
		if(!control::devHas(control::dev_model)&&!$force)
			return;
		$path = 'model/schema.'.$table.'.php';
		if(is_file($path)&&!self::tableExist($table)&&is_array($a=include($path)))
			R::storeMultiArray($a);
	}
	static function load($table,$id,$flag=0){
		if(is_integer($id))
			$w = 'id';
		else{
			$w = 'label';
			if($flag){
				if($flag&self::FLAG_ACCENT_INSENSITIVE){
					$w = 'uaccent('.$w.')';
					$id = str::unaccent($id);
				}
				if($flag&self::FLAG_CASE_INSENSITIVE){
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
}
