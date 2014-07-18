<?php namespace surikat\control;
use surikat\control;
use surikat\control\FS;
class sync{
	const TIMESPACE = 'sync_timespace/';
	const CACHE = 'sync_cache/';
	const EXT = '.sync';
	static function mtime($file,$sync,$forceCache=true){
		$syncF = control::$TMP.self::TIMESPACE.$sync.self::EXT;
		if($forceCache&&!is_file($syncF)){
			FS::mkdir($syncF,true);
			file_put_contents($syncF,'');
		}
		return @filemtime($file)<@filemtime($syncF);
	}
	static function update($sync){
		$syncF = control::$TMP.self::TIMESPACE.$sync.self::EXT;
		if(!is_file($syncF)){
			FS::mkdir($syncF,true);
			file_put_contents($syncF,'');
		}
		else
			touch($syncF);
	}
	
	static $spaceName = 'sync';
	static $className;
	static function __callStatic($c,$args){
		$id = sha1(serialize(array($c,$args)));
		$file = control::$TMP.self::CACHE.static::$spaceName.'/'.$id;
		if(strpos($c,'static')===0&&ctype_upper(substr($c,6,1)))
			return self::_statical(lcfirst(substr($c,6)),$args,$id,$file);
		elseif(strpos($c,'sync')===0&&ctype_upper(substr($c,4,1)))
			return self::_sync(lcfirst(substr($c,4)),$args,$id,$file);
		else
			return self::_dynTry($c,$args,$id,$file);
	}
	private static function _dynTry($c,$args,$id,$file){
		FS::mkdir($file,true);
		$data = null;
		try{
			file_put_contents($file,serialize($data=call_user_func_array(array((static::$className?static::$className:static::$spaceName),$c),$args)),LOCK_EX);
		}
		catch(\PDOException $e){
			$data = unserialize(file_get_contents($file));
		}
		return $data;
	}
	private static function _sync($c,$args,$id,$file){
		$sync = control::$TMP.self::TIMESPACE.static::$spaceName.'.'.$args[0].self::EXT;
		if(!($msync=@filemtime($sync))||@filemtime($file)<$msync)
			return self::_dynTry($c,$args,$id,$file);
		return unserialize(file_get_contents($file));
	}
	private static function _statical($c,$args,$id,$file){
		if(!file_exists($file))
			return self::_dynTry($c,$args,$id,$file);
		else
			return unserialize(file_get_contents($file));
	}
}