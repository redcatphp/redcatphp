<?php namespace Surikat\User;
use Surikat\FileSystem\FS;
use Surikat\HTTP\Domain;
use Surikat\User\SessionHandler;
use Surikat\Exception\Exception;
use Surikat\Dependency\Injector;
class Session{
	use Injector;
	private $id;
	private $key;
	private $handler;
	private $name = 'surikat';
	private $cookieLifetime = 0;
	private $maxAttempts = 10;
	protected $attemptsPath;
	protected $blockedWait = 1800;
	protected $regeneratePeriod = 3600;
	function __construct($name=null){
		$this->attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
		if($name)
			$this->setName($name);
	}
	function setName($name){
		$this->name = $name;
	}
	function exist(){
		return isset($_COOKIE[$this->name]);
	}
	function setCookieLifetime($time){
		$this->cookieLifetime = $time;
	}
	function &set(){
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
	function get(){
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
	function destroyKey($skey=null){
		self::sessionHandler()->destroyKey($skey);
	}
	function setKey($skey=null){
		self::destroyKey($skey);
		session_id(self::generateId());
		self::start();
		$tmp = [];
		foreach($_SESSION as $k=>$v)
			$tmp[$k] = $v;
		$_SESSION = [];
		session_destroy();
		session_write_close();
		$id = $this->id;
		if($p=strpos($id,'-'))
			$id = substr($id,$p+1);
		$id = $skey.'-'.$id;
		file_put_contents(self::getSavePath().self::getSessionName().'_'.$id,''); //prevent record a failed attempt
		session_id($id);
		session_start();
		$this->id = $id;
		foreach($tmp as $k=>$v)
			$_SESSION[$k] = $v;
		$this->key = $skey;
	}
	function checkBlocked(){
		if($s=self::isBlocked()){
			self::removeCookie();
			self::sessionHandler()->setWrite(false);
			throw new ExceptionSecurity(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function start(){
		if(!$this->id){
			session_name($this->name);
			$id = isset($_COOKIE[$this->name])?$_COOKIE[$this->name]:self::generateId();
			session_id($id);
			if(strpos($id,'-')!==false){
				self::checkBlocked();
				if(!is_file(self::getSavePath().$this->name.'_'.$id)){
					self::addAttempt();
					self::checkBlocked();
				}
			}
			if(session_start()){
				self::regenerate();
				$this->id = session_id();
			}
			else{
				throw new Exception('Unable to start session');
			}
		}
		return $this->id;
	}
	function destroy(){
		if(self::start()){
			$_SESSION = [];
			session_destroy();
			session_write_close();
			self::removeCookie();
			return true;
		}
	}
	function getSavePath(){
		return SURIKAT_TMP.'sessions/';
	}
	function getSessionName(){
		return str_replace('-','_',$this->name);
	}
	private function sessionHandler(){
		if(!isset($this->handler))
			$this->handler = new SessionHandler($this->name);
		return $this->handler;
	}
	function __destruct(){
		//write close
	}
	function generateId($prefix=''){
		return $prefix.base64_encode(hash('sha512',self::getRandomKey(rand(2,5)).uniqid('',true).self::getRandomKey(rand(2,5))));
	}
	function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	function addAttempt(){
		$ip = self::getIp();
		FS::mkdir($this->attemptsPath);
		if(is_file($this->attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents($this->attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents($this->attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	function isBlocked(){
		$ip = self::getIp();
		if(is_file($this->attemptsPath.$ip))
			$count = (int)file_get_contents($this->attemptsPath.$ip);
		else
			return false;
		$expiredate = filemtime($this->attemptsPath.$ip)+$this->blockedWait;
		$currentdate = time();
		if($count>=$this->maxAttempts){
			if($currentdate<$expiredate)
				return $expiredate-$currentdate;
			self::deleteAttempts();
			return false;
		}
		if($currentdate>$expiredate)
			self::deleteAttempts();
		return false;
	}
	function deleteAttempts(){
		$ip = self::getIp();
		return is_file($this->attemptsPath.$ip)&&unlink($this->attemptsPath.$ip);
	}
	
	static function setCookie($name, $value='', $expire = 0, $path = '', $domain='', $secure=false, $httponly=false){
        $_COOKIE[$name] = $value;
        //$this->cookieLifetime
		//'/'.Domain::getSuffixHref() //path
		//Domain::getServerHref() //domain
		//0 //secure
		//1 //httponly
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    static function removeCookie($name){
        unset($_COOKIE[$name]);
        return setcookie($name, NULL, -1);
    } 
}