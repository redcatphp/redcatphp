<?php namespace surikat\control;
class post{
	static $key = 'persistant';
	static function get_text($k,$default=null,$persistant=null){
		return htmlentities((string)self::get($k,$default,$persistant));
	}
	protected static function needSession(){
		if(!session_id())
			session_start();
	}
	static function clearPersistance($k=null,$p=null){
		self::needSession();
		$p = $p===null?sha1($_SERVER['PATH_INFO']):$p;
		if(isset($_SESSION[self::$key])&&isset($_SESSION[self::$key][$p])){
			if($k!==null){
				if(isset($_SESSION[self::$key][$p][$k]))
					$_SESSION[self::$key][$p][$k] = null;
			}
			else
				$_SESSION[self::$key][$p] = null;
		}
	}
	static function get($k,$default=null,$persistant=null,$p=null){
		self::needSession();
		$p = $p===null?sha1($_SERVER['PATH_INFO']):$p;
		if($persistant&&!isset($_SESSION[self::$key]))
			$_SESSION[self::$key] = array();
		if($persistant&&!isset($_SESSION[self::$key][$p]))
			$_SESSION[self::$key][$p] = array();
		if(isset($_POST[$k]))
			return $persistant?($_SESSION[self::$key][$p][$k] = $_POST[$k]):$_POST[$k];
		if($persistant&&isset($_SESSION[self::$key][$p])&&isset($_SESSION[self::$key][$p][$k]))
			return $_SESSION[self::$key][$p][$k];
		return $default;
	}
}
