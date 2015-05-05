<?php namespace StaxUser;
use ObjexLoader\MutatorCallTrait;
class Session{
	use MutatorCallTrait;
	private $id;
	private $key;
	private $name = 'surikat';
	private $maxAttempts = 10;
	private $cookieLifetime = 0;
	private $cookiePath;
	private $cookieDomain;
	protected $attemptsPath;
	protected $idLength = 100;
	protected $data = [];
	protected $modified;
	protected $saveRoot;
	protected $savePath;
	protected $splitter = '.';
	protected $gc_probability = 1;
	protected $gc_divisor = 100;
	protected $blockedWait = 1800; //half hour
	protected $maxLifetime = 31536000; //1 year
	protected $regeneratePeriod = 3600; //1 hour
	protected $_SessionHandler;
	protected $Http_Cookie;
	protected $handled;
	function __construct($name=null,$saveRoot=null,SessionHandlerInterface $sessionHandler=null){
		if(!$saveRoot){
			if(defined('SURIKAT_CWD'))
				$cwd = SURIKAT_CWD;
			else
				$cwd = getcwd();
			$saveRoot = $cwd.'/.tmp/sessions/';
		}
		if($name)
			$this->name = $name;
		$this->saveRoot = rtrim($saveRoot,'/').'/';
		$this->savePath = $this->saveRoot.$this->name.'/';
		$this->attemptsPath = getcwd().'/tmp/attempts/';
		$this->cookiePath = '/'.$this->FluxServer_Http_Url()->getSuffixHref();
		$this->cookieDomain = $this->FluxServer_Http_Url()->getServerHref();
		$this->checkBlocked();
		if(isset($sessionHandler))
			$this->_SessionHandler = $sessionHandler;
		else
			$this->_SessionHandler = $this->getDependency('_SessionHandler');
		$this->Http_Cookie = $this->FluxServer_Http_Cookie();
		$this->garbageCollector();
	}
	function handleReload(){
		if($this->handled)
			$this->handle();
	}
	function handleOnce(){
		if(!$this->handled){
			$this->handled = true;
			$this->handle();
		}
	}
	function handle(){
		$this->_SessionHandler->open($this->savePath,$this->name);
		if($this->clientExist()){
			$this->id = $this->clientId();
			$this->key = $this->clientKey();
			if($this->serverExist()){
				$this->data = (array)unserialize($this->_SessionHandler->read($this->getPrefix().$this->id));
				$this->autoRegenerateId();
			}
			else{
				$this->id = null;
				$this->key = null;
				$this->removeCookie($this->name,$this->cookiePath,$this->cookieDomain,false,true);
				$this->addAttempt();
				$this->checkBlocked();
			}
		}
		if(!isset($this->data['_FP_'])){
			$this->data['_FP_'] = $this->getClientFP();
		}
	}
	function garbageCollector(){
		if(mt_rand($this->gc_probability, $this->gc_divisor)===1)
			$this->_SessionHandler->gc($this->maxLifetime);
	}
	function destroy(){
		if($this->id)
			$this->_SessionHandler->destroy($this->getPrefix().$this->id);
		$this->_SessionHandler->close();
		$this->removeCookie($this->name,$this->cookiePath,$this->cookieDomain,false,true);
		return true;
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function setKey($key=null){
		$this->destroyKey($key);
		if($this->serverExist()){
			$old = $this->serverFile();
			$this->key = $key;
			$new = $this->serverFile();
			rename($old,$new);
		}
		else{
			$this->key = $key;
		}
		if(!$this->id)
			$this->id = $this->generateId();
		if($this->clientPrefix().$this->clientId()!=$this->getPrefix().$this->id){
			$this->writeCookie();
		}
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
			throw new SecurityException('Invalid session');
		}
		if($now>$mtime+$this->regeneratePeriod||$this->get('_FP_')!=$this->getClientFP()){
			$this->set('_FP_',$this->getClientFP());
			$this->regenerateId();
		}
	}
	function getName(){
		return $this->name;
	}
	function setName($name){
		$this->name = $name;
		$this->savePath = $this->saveRoot.$this->name.'/';
		$this->handleReload();
	}
	function serverFile(){
		$id = func_num_args()?func_get_arg(0):$this->getPrefix().$this->id;
		return $id?$this->savePath.$id:false;
	}
	function serverExist(){
		$id = func_num_args()?func_get_arg(0):$this->getPrefix().$this->id;
		return is_file($this->serverFile($id));
	}
	function cookie(){
		return $this->Http_Cookie[$this->name];
	}
	function clientId(){
		$cookie = $this->cookie();
		$pos = strpos($cookie,$this->splitter);
		if($cookie)
			return $pos===false?$cookie:substr($cookie,$pos+strlen($this->splitter));
	}
	function clientKey(){
		$cookie = $this->cookie();
		return $cookie?substr($cookie,0,strpos($cookie,$this->splitter)):null;
	}
	function clientPrefix(){
		$key = $this->clientKey();
		return $key?$key.$this->splitter:'';
	}
	function getPrefix(){
		return $this->key?$this->key.$this->splitter:'';
	}
	function clientExist(){
		return $this->cookie()!==null;
	}
	function setCookieLifetime($time){
		$this->cookieLifetime = $time;
	}
	function set(){
		$this->handleOnce();
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
		$this->handleOnce();
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
			$this->removeCookie($this->name,$this->cookiePath,$this->cookieDomain,false,true);
			$this->reset();
			throw new SecurityException(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function reset(){
		$this->data = [];
	}
	function start(){
		if(!$this->id){		
			$this->id = $this->generateId();
		}
		if($this->clientPrefix().$this->clientId()!=$this->getPrefix().$this->id){
			$this->writeCookie();
		}
	}
	function __destruct(){
		if($this->modified)
			$this->_SessionHandler->write($this->getPrefix().$this->id,serialize($this->data));
		else
			$this->_SessionHandler->touch($this->getPrefix().$this->id);
		$this->_SessionHandler->close();
	}
	function generateId(){
		return hash('sha512',$this->_RandomLib_Factory()->getMediumStrengthGenerator()->generate($this->idLength));
	}
	function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	function addAttempt(){
		$ip = $this->getIp();
		@mkdir($this->attemptsPath,0777,true);
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
		$this->setCookie(
			$this->name,
			$this->getPrefix().$this->id,
			($this->cookieLifetime?time()+$this->cookieLifetime:0),
			$this->cookiePath,
			$this->cookieDomain,
			false,
			true,
			false
		);
	}
	
	function __set($k,$v){
		$this->data[$k] = $v;
	}
	function __get($k){
		return $this->data[$k];
	}
	function setCookie($name, $value='', $expire = 0, $path = '', $domain='', $secure=false, $httponly=false, $global=true){
		if($expire&&isset($this->Http_Cookie[$name]))
			$this->removeCookie($name, $path, $domain, $secure, $httponly);
		if($global)
			$this->Http_Cookie[$name] = $value;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    function removeCookie($name, $path = '', $domain='', $secure=false, $httponly=false){
        unset($this->Http_Cookie[$name]);
        return setcookie($name, null, -1, $path, $domain, $secure, $httponly);
    }
}