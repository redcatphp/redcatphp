<?php namespace Surikat\User;
use Surikat\FileSystem\FS;
use Surikat\HTTP\Domain;
use Surikat\Exception\Exception;
use Surikat\Exception\Security as ExceptionSecurity;
use Surikat\DependencyInjection\MutatorCall;
use Surikat\User\SessionHandler;
class Session{
	use MutatorCall;
	private $id;
	private $key;
	private $name = 'surikat';
	private $maxAttempts = 10;
	private $cookieLifetime = 0;
	protected $attemptsPath;
	protected $idLength = 100;
	protected $data = [];
	protected $modified;
	protected $savePath;
	protected $splitter = '.';
	protected $gc_probability = 1;
	protected $gc_divisor = 100;
	protected $blockedWait = 1800; //half hour
	protected $maxLifetime = 31536000; //1 year
	protected $regeneratePeriod = 3600; //1 hour
	protected $User_SessionHandler;
	function __construct($name=null,$savePath=null,SessionHandler $sessionHandler=null){
		if(!$savePath)
			$savePath = SURIKAT_PATH.'.tmp/sessions/';
		if($name)
			$this->setName($name);
		$this->savePath = rtrim($savePath,'/').'/'.$this->name.'/';
		$this->attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
		if(isset($sessionHandler))
			$this->User_SessionHandler = $sessionHandler;
		else
			$this->User_SessionHandler = $this->getDependency('User_SessionHandler');
		$this->User_SessionHandler->open($this->savePath,$this->name);
		$this->checkBlocked();
		if($this->clientExist()){
			$this->id = $this->clientId();
			if($this->serverExist()){
				$this->data = (array)unserialize($this->User_SessionHandler->read($this->getPrefix().$this->id));
				$this->autoRegenerateId();
			}
			else{
				$this->id = null;
				$this->addAttempt();
				$this->checkBlocked();
			}
		}
		if(!isset($this->data['_FP_'])){
			$this->data['_FP_'] = $this->getClientFP();
		}
		if(mt_rand($this->gc_probability, $this->gc_divisor)===1)
			$this->User_SessionHandler->gc($this->maxLifetime);
	}
	function getPrefix(){
		return $this->key?$this->key.$this->splitter:'';
	}
	function destroy(){
		$this->User_SessionHandler->destroy($this->getPrefix().$id);
		$this->User_SessionHandler->close();
		self::removeCookie($this->name);
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function setKey($key=null){
		$this->destroyKey($key);
		$this->key = $key;
	}
	function regenerateId(){
		$old = $this->serverFile();
		$this->id = $this->generateId();
		$new = $this->serverFile();
		while(file_exists($new)){ //avoid collision
			$this->id = $this->generateId();
			$new = $this->serverFile();
		}
		rename($old,$new);
		$this->writeCookie();
	}
	function getClientFP(){
		return md5($_SERVER['REMOTE_ADDR'].' '.$_SERVER['HTTP_USER_AGENT']);
	}
	function autoRegenerateId(){
		$now = time();
		$mtime = filemtime($this->serverFile());
		if($now>$mtime+$this->maxLifetime){
			throw new ExceptionSecurity('Invalid session');
		}
		if($now>$mtime+$this->regeneratePeriod||$this->get('_FP_')!=$this->getClientFP()){
			$this->set('_FP_',$this->getClientFP());
			$this->regenerateId();
		}
	}
	function setName($name){
		$this->name = $name;
	}
	function serverFile(){
		$id = func_num_args()?func_get_arg(0):$this->id;
		return $id?$this->savePath.$id:false;
	}
	function serverExist(){
		$id = func_num_args()?func_get_arg(0):$this->id;
		return is_file($this->serverFile($id));
	}
	function clientId(){
		return $this->clientExist()?$_COOKIE[$this->name]:null;
	}
	function clientExist(){
		return isset($_COOKIE[$this->name]);
	}
	function setCookieLifetime($time){
		$this->cookieLifetime = $time;
	}
	function set(){
		$this->start();
		$this->modified = true;
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
		$args = func_get_args();
		$ref =& $this->data;
		foreach($args as $k){
			if(is_array($ref)&&isset($ref[$k]))
				$ref =& $ref[$k];
			else{
				unset($ref);
				$ref = null;
				break;
			}
		}
		return $ref;
	}
	function checkBlocked(){
		if($s=$this->isBlocked()){
			self::removeCookie($this->name);
			$this->reset();
			throw new ExceptionSecurity(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function reset(){
		$this->data = [];
	}
	function start(){
		if(!$this->id){			
			$this->id = $this->generateId();
			$this->writeCookie();
		}
		return $this->id;
	}
	function __destruct(){
		if($this->modified)
			$this->User_SessionHandler->write($this->getPrefix().$this->id,serialize($this->data));
		$this->User_SessionHandler->close();
	}
	function generateId(){
		return hash('sha512',$this->Crypto_RandomLib_Factory()->getMediumStrengthGenerator()->generate($this->idLength));
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
			$this->getPrefix().$this->id,
			($this->cookieLifetime?time()+$this->cookieLifetime:0),
			'/'.Domain::getSuffixHref(),
			Domain::getServerHref(),
			false,
			true
		);
	}
	
	function __set($k,$v){
		$this->data[$k] = $v;
	}
	function __get($k){
		return $this->data[$k];
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