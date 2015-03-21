<?php namespace Surikat\User;
use Surikat\Vars\ArrayObject;
use Surikat\DependencyInjection\Container;
class Post extends ArrayObject{
	private static $postObject;
	function offsetGet($k){
		return ($v=parent::offsetGet($k))===''?null:$v;
	}
	function __get($k){
		return ($v=parent::__get($k))===''?null:$v;
	}
	static function getObject(){
		if(!isset(self::$postObject))
			self::$postObject = new static($_POST);
		return self::$postObject;
	}
	static $key = 'persistant';
	static function get_text($k,$default=null,$persistant=null){
		return htmlentities((string)self::get($k,$default,$persistant));
	}
	static function clearPersistance($k=null,$p=null){
		Container::get('User\Session')->start();
		$p = $p===null?sha1(@$_SERVER['PATH_INFO']):$p;
		if(isset($_SESSION[self::$key])&&isset($_SESSION[self::$key][$p])){
			if($k!==null){
				if(isset($_SESSION[self::$key][$p][$k]))
					$_SESSION[self::$key][$p][$k] = null;
			}
			else
				$_SESSION[self::$key][$p] = null;
		}
	}
	static function get_checked($k,$default=null,$persistant=null,$p=null){
		return ($c=self::get($k,false,$persistant,$p,true)!==false?'checked':($default?$default:''))?'checked="'.$c.'"':'';
	}
	static function get($k,$default=null,$persistant=null,$p=null,$ifn=null){
		if($persistant)
			Container::get('User\Session')->start();
		if(strpos($k,'[')!==false){
			$x = explode('[',str_replace(']','',$k));
			$r = self::get(($k=array_shift($x)),null,$persistant,$p);
			foreach($x as $_x){
				if($_x=='')
					$_x = 0;
				if(is_array($r)&&isset($r[$_x]))
					$r =& $r[$_x];
				else
					return $default;
			}
			return $r;
		}
		$p = $p===null?sha1(@$_SERVER['PATH_INFO']):$p;
		if($persistant&&!isset($_SESSION[self::$key]))
			$_SESSION[self::$key] = [];
		if($persistant&&!isset($_SESSION[self::$key][$p]))
			$_SESSION[self::$key][$p] = [];
		if($ifn&&!isset($_POST[$k]))
			$_POST[$k] = false;
		if(isset($_POST[$k]))
			return $persistant?($_SESSION[self::$key][$p][$k] = $_POST[$k]):$_POST[$k];
		if($persistant&&isset($_SESSION[self::$key][$p])&&isset($_SESSION[self::$key][$p][$k]))
			return $_SESSION[self::$key][$p][$k];
		return $default;
	}
}