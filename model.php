<?php namespace surikat;
use surikat\model\R;
use surikat\model\RedBean\OODBBean;
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
}
?>
