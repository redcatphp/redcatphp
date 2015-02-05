<?php namespace Surikat\Core;
use Surikat\Core\FS;
use Surikat\Core\Domain;
use Surikat\Core\SessionHandler;
use Surikat\Core\Exception;
class Session{
	private static $id;
	private static $key;
	private static $handler;
	private static $sessionName = 'surikat';
	private static $cookieLifetime = 0;
	private static $maxAttempts = 10;
	protected static $attemptsPath;
	protected static $blockedWait = 1800;
	protected static $regeneratePeriod = 3600;
	static function __initialize(){
		self::$attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
	}
	static function setName($name){
		self::$sessionName = $name;
	}
	static function exist(){
		return isset($_COOKIE[self::$sessionName]);
	}
	static function setCookieLifetime($time){
		self::$cookieLifetime = $time;
	}
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
	static function get(){
		if(!self::exist())
			return;
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
	static function destroyKey($skey=null){
		self::sessionHandler()->destroyKey($skey);
	}
	static function setKey($skey=null){
		self::destroyKey($skey);
		self::start();
		$tmp = [];
		foreach($_SESSION as $k=>$v)
			$tmp[$k] = $v;
		$_SESSION = [];
		session_destroy();
		session_write_close();
		$id = self::$id;
		if($p=strpos($id,'-'))
			$id = substr($id,$p+1);
		$id = $skey.'-'.$id;
		file_put_contents(self::getSavePath().self::getSessionName().'_'.$id,''); //prevent record a failed attempt
		session_id($id);
		session_start();
		self::$id = $id;
		foreach($tmp as $k=>$v)
			$_SESSION[$k] = $v;
		self::$key = $skey;
	}
	static function checkBlocked(){
		if($s=self::isBlocked()){
			self::removeCookie();
			self::sessionHandler()->setWrite(false);
			throw new ExceptionSecurity(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	static function start(){
		if(!self::$id){
			self::handle();
			session_name(self::$sessionName);
			$id = isset($_COOKIE[self::$sessionName])?$_COOKIE[self::$sessionName]:self::generateId();
			session_id($id);
			if(strpos($id,'-')!==false){
				self::checkBlocked();
				if(!is_file(self::getSavePath().self::$sessionName.'_'.$id)){
					self::addAttempt();
					self::checkBlocked();
				}
			}
			if(session_start()){
				self::regenerate();
				self::$id = session_id();
			}
			else{
				throw new Exception('Unable to start session');
			}
		}
		return self::$id;
	}
	static function destroy(){
		if(self::start()){
			$_SESSION = [];
			session_destroy();
			session_write_close();
			self::removeCookie();
			return true;
		}
	}
	static function removeCookie(){
		setcookie(self::$sessionName,null,-1,ini_get('session.cookie_path'),ini_get('session.cookie_domain'),false,true);
	}
	static function getSavePath(){
		return SURIKAT_TMP.'sessions/';
	}
	static function getSessionName(){
		return str_replace('-','_',self::$sessionName);
	}
	private static function sessionHandler(){
		if(!isset(self::$handler)){
			$d = self::getSavePath();
			@ini_set('session.gc_probability',1);
			@ini_set('session.gc_divisor',1000);
			@ini_set('session.gc_maxlifetime',3600);
			ini_set('session.save_path',$d);
			ini_set('session.use_cookies',1);
			ini_set('session.use_only_cookies',1);
			FS::mkdir($d);
			self::$handler = new SessionHandler(self::$sessionName);
		}
		return self::$handler;
	}
	private static function handle(){
		$handler = self::sessionHandler();
		//session_set_cookie_params(self::$cookieLifetime,'/'.Domain::getSuffixHref(),Domain::getServerHref(),false,true);
		ini_set('session.cookie_lifetime',self::$cookieLifetime);
		ini_set('session.cookie_path','/'.Domain::getSuffixHref());
		ini_set('session.cookie_domain',Domain::getServerHref());
		ini_set('session.cookie_secure',0);
		ini_set('session.cookie_httponly',1);
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
			$_SESSION['_EXPIRE_'] = $now+self::$regeneratePeriod;
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
			session_id(self::generateId());
			session_start();
		}
		elseif($now>=$_SESSION['_EXPIRE_']||$_SESSION['_IP_']!=$_SERVER['REMOTE_ADDR']||$_SESSION['_AGENT_']!=$_SERVER['HTTP_USER_AGENT']){
			$_SESSION['_EXPIRE_'] = $now+self::$regeneratePeriod;
			$tmp = [];
			foreach($_SESSION as $k=>$v)
				$tmp[$k] = $v;
			$id = session_id();
			$prefix = '';
			if($p=strpos($id,'-'))
				$prefix = substr($id,0,$p).'-';
			session_destroy();
			session_write_close();
			$sid = self::generateId($prefix);
			session_id($sid);
			session_start();
			foreach($tmp as $k=>$v)
				$_SESSION[$k] = $v;
		}
	}
	static function generateId($prefix=''){
		return $prefix.base64_encode(hash('sha512',uniqid('',true).uniqid('',true)));
	}
	static function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	static function addAttempt(){
		$ip = self::getIp();
		FS::mkdir(self::$attemptsPath);
		if(is_file(self::$attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents(self::$attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents(self::$attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	static function isBlocked(){
		$ip = self::getIp();
		if(is_file(self::$attemptsPath.$ip))
			$count = (int)file_get_contents(self::$attemptsPath.$ip);
		else
			return false;
		$expiredate = filemtime(self::$attemptsPath.$ip)+self::$blockedWait;
		$currentdate = time();
		if($count>=self::$maxAttempts){
			if($currentdate<$expiredate)
				return $expiredate-$currentdate;
			self::deleteAttempts();
			return false;
		}
		if($currentdate>$expiredate)
			self::deleteAttempts();
		return false;
	}
	static function deleteAttempts(){
		$ip = self::getIp();
		return is_file(self::$attemptsPath.$ip)&&unlink(self::$attemptsPath.$ip);
	}
}
Session::__initialize();