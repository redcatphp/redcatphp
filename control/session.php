<?php namespace surikat\control;
use surikat\control;
class session{
	static $id;
	static function &set(){
		self::start();
		$args = func_get_args();
		$v = array_pop($args);
		if(empty($args)){
			$_SESSION[$v] = null;
			return;
		}
		$ref =& $_SESSION;
		foreach($args as $k){
			if(!is_array($ref))
				$ref = array();
			$ref =& $ref[$k];
		}
		$ref = $v;
		return $ref;
	}
	static function &get(){
		self::start();
		$args = func_get_args();
		$ref =& $_SESSION;
		foreach($args as $k)
			if(is_array($ref)&&isset($ref[$k]))
				$ref =& $ref[$k];
			else
				return null;
		return $ref;
	}
	static function start($name='project'){
		if(self::$id)
			return;
		self::handle();
		session_name("surikat_".$name);
		session_start();
		self::regenerate();
		self::$id = session_id();
	}
	static function destroy($name='project'){
		self::start($name);
		$_SESSION = array();
		session_destroy();
		session_write_close();
	}
	private static function handle(){
		@ini_set('session.gc_probability',1);			// Initialise le garbage collector (rares bugs php)
		@ini_set('session.gc_divisor',1000);			// Idem
		@ini_set('session.gc_maxlifetime',3600);
		ini_set("session.save_path",$d=control::$TMP.'sessions/');
		FS::mkdir($d);
		$handler = new session_handler();
		session_set_save_handler(
			array($handler, 'open'),
			array($handler, 'close'),
			array($handler, 'read'),
			array($handler, 'write'),
			array($handler, 'destroy'),
			array($handler, 'gc')
		);
		register_shutdown_function('session_write_close');
	}
	private static function regenerate(){
		$now = time();
		$hash = sha1(HTTP::getRealIpAddr());
		if(!isset($_SESSION['sess_expiration'])){
			$_SESSION['sess_expiration'] = $now+ini_get('session.gc_maxlifetime');
			$_SESSION['sess_hash'] = $hash;
		}
		if(
			!isset($_SESSION['sess_hash'])
			||($_SESSION['sess_hash']!=$hash)
			||($_SESSION['sess_expiration']<=$now-session_handler::$maxNoConnectionTime)
		){
			session_destroy();
			session_write_close();
			session_start();
		}
		if($now>=$_SESSION['sess_expiration']){
			session_regenerate_id(true);
			$sid = session_id();
			session_write_close();
			session_id($sid);
			session_start();
		}
	}
}
