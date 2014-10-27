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
				$ref = [];
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
			else{
				$ref = null;
				break;
			}
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
		$_SESSION = [];
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
			[$handler, 'open'],
			[$handler, 'close'],
			[$handler, 'read'],
			[$handler, 'write'],
			[$handler, 'destroy'],
			[$handler, 'gc']
		);
		register_shutdown_function('session_write_close');
	}
	private static function regenerate(){
		$now = time();
		$hash = sha1($_SERVER['REMOTE_ADDR']);
		if(!isset($_SESSION['expire'])){
			$_SESSION['expire'] = $now+ini_get('session.gc_maxlifetime');
			$_SESSION['hash'] = $hash;
		}
		if(
			!isset($_SESSION['hash'])
			||($_SESSION['hash']!=$hash)
			||($_SESSION['expire']<=$now-session_handler::$maxNoConnectionTime)
		){
			session_destroy();
			session_write_close();
			session_start();
		}
		elseif($now>=$_SESSION['expire']){
			$_SESSION['expire'] = $now+ini_get('session.gc_maxlifetime');
			session_regenerate_id(true);
			$sid = session_id();
			session_write_close();
			session_id($sid);
			session_start();
		}
	}
}
