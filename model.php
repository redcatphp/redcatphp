<?php namespace surikat;
use surikat\model\R;
use surikat\model\Compo;
use surikat\model\RedBeanPHP\OODBBean;
class model {
	private static $AVAILABLE;
	static $DEBUG;
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
	static function debug($mod=true,$r=null){
		if($r!==null&&isset(R::$adapter))
			R::debug(!!$r);
		self::$DEBUG = $mod;
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
	static function load($table,$id){
		return R::findOne($table,'WHERE '.(is_integer($id)?'id':'label').'=?',array($id));
	}
}
