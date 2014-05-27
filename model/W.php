<?php namespace surikat\model;
use surikat\control;
use surikat\control\FS;
class W {
	static function __callStatic($c,$args){
		$id = sha1(serialize(array($c,$args)));
		$file = control::$TMP.'cache/.db/'.$id;
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
			file_put_contents($file,serialize($data=call_user_func_array(array('model',$c),$args)),LOCK_EX);
		}
		catch(\PDOException $e){
			$data = unserialize(file_get_contents($file));
		}
		return $data;
	}
	private static function _sync($c,$args,$id,$file){
		$sync = control::$TMP.'cache/.db/'.$args[0].'.sync';
		if(!($msync=@filemtime($sync))||@filemtime($file)<$msync)
			return self::_dynTry($c,$args,$id,$file);
	}
	private static function _statical($c,$args,$id,$file){
		if(!file_exists($file))
			return self::_dynTry($c,$args,$id,$file);
		else
			return unserialize(file_get_contents($file));
	}
}
