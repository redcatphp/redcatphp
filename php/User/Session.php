<?php namespace Surikat\User;
use Surikat\FileSystem\FS;
use Surikat\HTTP\Domain;
use Surikat\Exception\Exception;
use Surikat\Exception\Security as ExceptionSecurity;
use Surikat\DependencyInjection\MutatorMagic;
use Surikat\Crypto\RandomLib\Factory as RandomLib_Factory;
class Session{
	use MutatorMagic;
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
	protected $sessionName;
	protected $gc_probability = 1;
	protected $gc_divisor = 100;
	protected $blockedWait = 1800; //half hour
	protected $maxLifetime = 31536000; //1 year
	protected $regeneratePeriod = 3600; //1 hour
	function __construct($sessionName='surikat',$savePath=null){
		if(!$savePath)
			$savePath = SURIKAT_PATH.'.tmp/sessions/';
		$this->sessionName = $sessionName;
		$this->savePath = rtrim($savePath,'/').'/'.$sessionName.'/';
		if($this->clientExist()){
			$this->id = $this->clientId();
			$this->open();
		}
		$this->autoRegenerateId();
		$this->attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
		if($sessionName)
			$this->setName($sessionName);
		if(mt_rand($this->gc_probability, $this->gc_divisor)===1)
			$this->gc($this->maxLifetime);
	}
	function getPrefix(){
		return $this->key?$this->key.$this->splitter:'';
	}
	function open(){
		$id = func_num_args()?func_get_arg(0):$this->id;
		if(is_file($this->savePath.$this->getPrefix().$id))
			$this->data = (array)@unserialize(file_get_contents($this->savePath.$this->getPrefix().$id));
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function setKey($key=null){
		$this->destroyKey($key);
		$this->key = $key;
	}
	function write(){
		$id = func_num_args()?func_get_arg(0):$this->id;
		$data = func_num_args()>1?func_get_arg(1):$this->data;
		if(!is_dir($this->savePath))
			@mkdir($this->savePath,0777,true);
		if(!empty($this->data))
			return file_put_contents($this->savePath.$this->getPrefix().$id, serialize($this->data), LOCK_EX) === false ? false : true;
	}
	function destroy($id){
		$file = $this->savePath.$this->getPrefix().$id;
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
		$old = $this->serverFile();
		$this->id = $this->generateId();
		$new = $this->serverFile();
		while(file_exists($new)){ //avoid collision
			$this->id = $this->generateId();
			$new = $this->serverFile();
		}
		$this->writeCookie();
		rename($old,$new);
	}
	function getClientFP(){
		return md5($_SERVER['REMOTE_ADDR'].' '.$_SERVER['HTTP_USER_AGENT']);
	}
	function autoRegenerateId(){
		$now = time();
		$file = $this->serverFile();
		if(!$file||!file_exists($file)){
			$this->data['_FP_'] = $this->getClientFP();
			return;
		}
		$mtime = filemtime($file);
		if($now>$mtime+$this->maxLifetime){
			throw new ExceptionSecurity('Invalid session');
		}
		if($now>$mtime+$this->regeneratePeriod||$this->data['_FP_']!=$this->getClientFP()){
			$this->data['_FP_'] = $this->getClientFP();
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
	function &set(){
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
			$this->id = $this->generateId();
			$this->writeCookie();
			if(strpos($this->id,'.')!==false){
				$this->checkBlocked();
				if(!$this->serverExist()){
					$this->addAttempt();
					$this->checkBlocked();
				}
			}
		}
		return $this->id;
	}
	function __destruct(){
		if($this->modified)
			$this->write();
	}
	function generateId(){
		return hash('sha512',$this->Randomizator->generate($this->idLength));
	}
	function Randomizator(){
		return (new RandomLib_Factory)->getMediumStrengthGenerator();
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