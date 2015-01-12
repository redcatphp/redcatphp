<?php namespace Surikat\Core;
use Surikat\Core\FS;
use Surikat\Core\SessionHandler;
class Session{
	private static $id;
	private static $key;
	private static $handler;
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
	static function destroyKey($skey=null,$name='projet'){
		self::sessionHandler()->destroyKey($skey);
	}
	static function setKey($skey=null,$name='projet'){
		if(!self::$id)
			self::start($name);
		$tmp = [];
		foreach($_SESSION as $k=>$v)
			$tmp[$k] = $v;
		$_SESSION = [];
		session_destroy();
		session_id($skey.'.'.self::$id);
		session_start();
		foreach($tmp as $k=>$v)
			$_SESSION[$k] = $v;
		self::$key = $skey;
	}
	static function start($name='project'){
		if(!self::$id){
			self::handle();
			session_name("surikat_".$name);
			if(session_start()){
				self::regenerate();
				self::$id = session_id();
			}
		}
		return self::$id;
	}
	static function destroy($name='project'){
		if(self::start($name)){
			$_SESSION = [];
			session_destroy();
			session_write_close();
			return true;
		}
	}
	private static function sessionHandler(){
		if(!isset(self::$handler)){
			$d = SURIKAT_TMP.'sessions/';
			@ini_set('session.gc_probability',1);			// Initialise le garbage collector (rares bugs php)
			@ini_set('session.gc_divisor',1000);			// Idem
			@ini_set('session.gc_maxlifetime',3600);
			ini_set("session.save_path",$d);
			FS::mkdir($d);
			self::$handler = new SessionHandler();
		}
		return self::$handler;
	}
	private static function handle(){
		$handler = self::sessionHandler();
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
		if(!isset($_SESSION['_EXPIRE_'])){
			$_SESSION['_EXPIRE_'] = $now+ini_get('session.gc_maxlifetime');
			$_SESSION['_IP_'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['_AGENT_'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if(
			!isset($_SESSION['_IP_'])
			||!isset($_SESSION['_AGENT_'])
			||($_SESSION['_IP_']!=$_SERVER['REMOTE_ADDR']&&$_SESSION['_AGENT_']!=$_SERVER['HTTP_USER_AGENT'])
			||($_SESSION['_EXPIRE_']<=$now-SessionHandler::$maxNoConnectionTime)
		){
			session_destroy();
			session_write_close();
			session_start();
		}
		elseif($now>=$_SESSION['_EXPIRE_']||$_SESSION['_IP_']!=$_SERVER['REMOTE_ADDR']||$_SESSION['_AGENT_']!=$_SERVER['HTTP_USER_AGENT']){
			$_SESSION['_EXPIRE_'] = $now+ini_get('session.gc_maxlifetime');
			session_regenerate_id(true);
			$sid = session_id();
			session_write_close();
			session_id($sid);
			session_start();
		}
	}
}
