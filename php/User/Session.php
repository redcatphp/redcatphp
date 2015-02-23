<?php namespace Surikat\User;
use Surikat\FileSystem\FS;
use Surikat\HTTP\Domain;
use Surikat\Exception\Exception;
use Surikat\Exception\Security as ExceptionSecurity;
use Surikat\DependencyInjection\MutatorMagic;
class Session{
	use MutatorMagic;
	private $id;
	private $key;
	private $name = 'surikat';
	private $cookieLifetime = 0;
	private $maxAttempts = 10;
	protected $attemptsPath;
	protected $blockedWait = 1800;
	protected $data = [];
	protected $modified;
	protected $savePath;
	protected $splitter = '.';
	protected $sessionName;
	protected $maxLifetime = 31536000; //1 year
	protected $regeneratePeriod = 3600;
	function __construct($sessionName='surikat',$savePath=null){
		$this->sessionName = $sessionName;
		if(!$savePath)
			$savePath = SURIKAT_PATH.'.tmp/sessions/';
		$savePath = rtrim($savePath,'/').'/';
		$this->savePath = $savePath.'/'.$sessionName.'/';
		$this->sessionName = $savePath.'/'.$sessionName.'/';
		$this->open();
		$this->autoRegenerateId();
		$this->attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
		if($sessionName)
			$this->setName($sessionName);
		if(mt_rand(1, 100)===1)
			$this->gc($this->maxLifetime);
	}
	function open(){
		if(is_file($this->savePath.$this->key.$this->splitter.$id))
			$this->data = (array)@unserialize(file_get_contents($this->savePath.$this->key.$this->splitter.$id));
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function setKey($key=null){
		$this->destroyKey($key);
		$this->key = $key;
	}
	function write($id, $data){
		if(!is_dir($this->savePath))
			@mkdir($this->savePath,0777,true);
		if(!empty($this->data))
			return file_put_contents($this->savePath.$this->key.$this->splitter.$id, serialize($this->data), LOCK_EX) === false ? false : true;
	}
	function destroy($id){
		$file = $this->savePath.$this->key.$this->splitter.$id;
		if(file_exists($file))
			unlink($file);
		self::removeCookie($this->name);
	}
	function gc($max){
		$check = time()-$max;
		foreach(glob($this->savePath.'*') as $file){
			if(filemtime($file)<$check){
				@unlink($file);
			}
		}
		return true;
	}
	function regenerateId(){
		
	}
	function autoRegenerateId(){
		$now = time();
		if(!isset($this->data['_EXPIRE_'])){
			$this->data['_EXPIRE_'] = $now+$this->regeneratePeriod;
			$this->data['_IP_'] = $_SERVER['REMOTE_ADDR'];
			$this->data['_AGENT_'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if(
			!isset($this->data['_IP_'])
			||!isset($this->data['_AGENT_'])
			||($this->data['_IP_']!=$_SERVER['REMOTE_ADDR']&&$this->data['_AGENT_']!=$_SERVER['HTTP_USER_AGENT'])
			||($this->data['_EXPIRE_']<=$now-$this->maxLifetime)
		){
			$this->regenerateId();
		}
		elseif($now>=$this->data['_EXPIRE_']||$this->data['_IP_']!=$_SERVER['REMOTE_ADDR']||$this->data['_AGENT_']!=$_SERVER['HTTP_USER_AGENT']){
			$this->data['_EXPIRE_'] = $now+$this->regeneratePeriod;
			$this->regenerateId();
		}
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
		$this->start();
		$args = func_get_args();
		$v = array_pop($args);
		if(empty($args)){
			$this->data[$v] = null;
			return;
		}
		$ref =& $this->data;
		foreach($args as $k){
			if(!is_array($ref))
				$ref = [];
			$ref =& $ref[$k];
		}
		$ref = $v;
		return $ref;
	}
	function get(){
		if(!$this->exist())
			return;
		$this->start();
		$args = func_get_args();
		$ref =& $this->data;
		foreach($args as $k)
			if(is_array($ref)&&isset($ref[$k]))
				$ref =& $ref[$k];
			else{
				$ref = null;
				break;
			}
		return $ref;
	}
	function checkBlocked(){
		if($s=$this->isBlocked()){
			self::removeCookie();
			$this->reset();
			throw new ExceptionSecurity(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function reset(){
		$this->data = [];
	}
	function start(){
		if(!$this->id){
			$id = isset($_COOKIE[$this->name])?$_COOKIE[$this->name]:$this->generateId();
			$this->setId($id);
			if(strpos($id,'.')!==false){
				$this->checkBlocked();
				if(!is_file($this->savePath.$this->name.'_'.$id)){
					$this->addAttempt();
					$this->checkBlocked();
				}
			}
			if(session_start()){
				$this->regenerate();
				$this->id = session_id();
			}
			else{
				throw new Exception('Unable to start session');
			}
		}
		return $this->id;
	}
	function __destruct(){
		var_dump(__LINE__);
		$this->write();
	}
	function generateId(){
		return uniqid();
	}
	function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	function addAttempt(){
		$ip = $this->getIp();
		FS::mkdir($this->attemptsPath);
		if(is_file($this->attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents($this->attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents($this->attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	function isBlocked(){
		$ip = $this->getIp();
		if(is_file($this->attemptsPath.$ip))
			$count = (int)file_get_contents($this->attemptsPath.$ip);
		else
			return false;
		$expiredate = filemtime($this->attemptsPath.$ip)+$this->blockedWait;
		$currentdate = time();
		if($count>=$this->maxAttempts){
			if($currentdate<$expiredate)
				return $expiredate-$currentdate;
			$this->deleteAttempts();
			return false;
		}
		if($currentdate>$expiredate)
			$this->deleteAttempts();
		return false;
	}
	function deleteAttempts(){
		$ip = $this->getIp();
		return is_file($this->attemptsPath.$ip)&&unlink($this->attemptsPath.$ip);
	}
	
	function writeCookie(){
		self::setCookie(
			$this->name,
			$this->key.$this->splitter.$this->id,
			time()+$this->cookieLifetime,
			'/'.Domain::getSuffixHref(),
			Domain::getServerHref(),
			false,
			true
		);
	}
	
	static function getCookie($name){
        return isset($_COOKIE[$name])?$_COOKIE[$name]:null;
	}
	static function setCookie($name, $value='', $expire = 0, $path = '', $domain='', $secure=false, $httponly=false){
        $_COOKIE[$name] = $value;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    static function removeCookie($name){
        unset($_COOKIE[$name]);
        return setcookie($name, NULL, -1);
    }
}