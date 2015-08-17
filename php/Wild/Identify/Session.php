<?php namespace Wild\Identify;
class Session{
	private $id;
	private $key;
	private $name;
	private $maxAttempts = 10;
	private $cookieLifetime;
	private $cookiePath;
	private $cookieDomain;
	protected $attemptsPath;
	protected $idLength = 100;
	protected $data = [];
	protected $origin = [];
	protected $saveRoot;
	protected $savePath;
	protected $splitter = '.';
	protected $gc_probability = 1;
	protected $gc_divisor = 100;
	protected $blockedWait = 1800; //half hour
	protected $maxLifetime;
	protected $regeneratePeriod = 3600; //1 hour
	protected $SessionHandler;
	protected $Cookie;
	protected $handled;
    
    protected $baseHref;
	protected $suffixHref;
	protected $server;
	function __construct(SessionHandlerInterface $sessionHandler,
		$name,
		$saveRoot,
		$server=null,
		$cookieLifetime=0,
		$maxLifetime=31536000  //1 year
		
	){
		$this->name = $name;
		$this->saveRoot = rtrim($saveRoot,'/').'/';
		$this->savePath = $this->saveRoot.$this->name.'/';
		$this->attemptsPath = getcwd().'/.tmp/attempts/';
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
		$this->cookiePath = $this->getSuffixHref();
		$this->cookieDomain = $this->getServerHref();
		$this->cookieLifetime = $cookieLifetime;
		$this->maxLifetime = $maxLifetime;
		$this->checkBlocked();
		if(!isset($sessionHandler))
			$sessionHandler = new SessionHandler();
		$this->SessionHandler = $sessionHandler;
		$this->Cookie = &$_COOKIE;
		$this->garbageCollector();
		
	}
	function handle($reload=false){
		if($this->handled&&(!$reload||!$this->handled))
			return;
		$this->handled = true;
		$this->SessionHandler->open($this->savePath,$this->name);
		if($this->clientExist()){
			$this->id = $this->clientId();
			$this->key = $this->clientKey();
			if($this->serverExist()){
				$this->origin = $this->data = (array)unserialize($this->SessionHandler->read($this->getPrefix().$this->id));
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
		$this->origin['_FP_'] = $this->data['_FP_'];
	}
	function garbageCollector(){
		if(mt_rand($this->gc_probability, $this->gc_divisor)===1){
			$this->SessionHandler->gc($this->maxLifetime);
			if(is_dir($this->attemptsPath)&&($dh = opendir($this->attemptsPath))){
				while($f=readdir($dh)){
					$file = $this->attemptsPath.$f;
					if(is_file($file)&&time()>filemtime($file)+$this->blockedWait){
						unlink($file);
					}
				}
			}
		}
	}
	function destroy(){
		if($this->id)
			$this->SessionHandler->destroy($this->getPrefix().$this->id);
		$this->SessionHandler->close();
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
			$this->id = $this->clientId();
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
			$this->destroy();
			$this->reset();
			$this->id = $this->generateId();
			$this->writeCookie();
		}
		elseif($now>$mtime+$this->regeneratePeriod||$this->get('_FP_')!=$this->getClientFP()){
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
		$this->handle(true);
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
		return isset($this->Cookie[$this->name])?$this->Cookie[$this->name]:null;
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
	function checkBlocked(){
		if($s=$this->isBlocked()){
			$this->removeCookie($this->name,$this->cookiePath,$this->cookieDomain,false,true);
			$this->reset();
			throw new SecurityException(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function reset(){
		$this->origin = $this->data = [];
	}
	function isModified(){
		return $this->origin!==$this->data;
	}
	function __destruct(){
		if($this->isModified()){
			if(!$this->id)
				$this->id = $this->generateId();
			if($this->clientPrefix().$this->clientId()!=$this->getPrefix().$this->id){
				$this->writeCookie();
			}
			$this->SessionHandler->write($this->getPrefix().$this->id,serialize($this->data));
		}
		else{
			if($this->id)
				$this->SessionHandler->touch($this->getPrefix().$this->id);
		}
		$this->SessionHandler->close();
	}
	function generateId(){
		return hash('sha512',(new RandomLib\Factory())->getMediumStrengthGenerator()->generate($this->idLength));
	}
	function getIp(){
		return $this->server['REMOTE_ADDR'];
	}
	function getIpHash(){
		return sha1($this->getIp());
	}
	function addAttempt(){
		$ip = $this->getIpHash();
		@mkdir($this->attemptsPath,0777,true);
		if(is_file($this->attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents($this->attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents($this->attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	function isBlocked(){
		$ip = $this->getIpHash();
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
		$ip = $this->getIpHash();
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
		$this->handle();
		$this->data[$k] = $v;
	}
	function &__get($k){
		$this->handle();
		return $this->data[$k];
	}
	function __isset($k){
		$this->handle();
		return isset($this->data[$k]);
	}
	function __unset($k){
		if(isset($this->data[$k])){
			$this->handle();
			unset($this->data[$k]);
		}
	}
	function setCookie($name, $value='', $expire = 0, $path = '', $domain='', $secure=false, $httponly=false, $global=true){
		if($expire&&isset($this->Cookie[$name]))
			$this->removeCookie($name, $path, $domain, $secure, $httponly);
		if($global)
			$this->Cookie[$name] = $value;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    function removeCookie($name, $path = '', $domain='', $secure=false, $httponly=false){
		if(isset($this->Cookie[$name]))
			unset($this->Cookie[$name]);
        return setcookie($name, null, -1, $path, $domain, $secure, $httponly);
    }
    
	function setBaseHref($href){
		$this->baseHref = $href;
	}
	function getProtocolHref(){
		return 'http'.(@$this->server["HTTPS"]=="on"?'s':'').'://';
	}
	function getServerHref(){
		return $this->server['SERVER_NAME'];
	}
	function getPortHref(){
		$ssl = @$this->server["HTTPS"]=="on";
		return @$this->server['SERVER_PORT']&&((!$ssl&&(int)$this->server['SERVER_PORT']!=80)||($ssl&&(int)$this->server['SERVER_PORT']!=443))?':'.$this->server['SERVER_PORT']:'';
	}
	function getBaseHref(){
		if(!isset($this->baseHref)){
			$this->setBaseHref($this->getProtocolHref().$this->getServerHref().$this->getPortHref().'/');
		}
		return $this->baseHref.$this->getSuffixHref();
	}
	function setSuffixHref($href){
		$this->suffixHref = $href;
	}
	function getSuffixHref(){
		if(!isset($this->suffixHref)){
			if(isset($this->server['SURIKAT_URI'])){
				$this->suffixHref = $this->server['SURIKAT_URI'];
			}
			else{
				$docRoot = $this->server['DOCUMENT_ROOT'].'/';
				//$docRoot = dirname($this->server['SCRIPT_FILENAME']).'/';
				if(defined('SURIKAT_CWD'))
					$cwd = SURIKAT_CWD;
				else
					$cwd = getcwd();
				if($docRoot!=$cwd&&strpos($cwd,$docRoot)===0)
					$this->suffixHref = substr($cwd,strlen($docRoot));
			}
		}
		return $this->suffixHref;
	}
	function getSubdomainHref($sub=''){
		$lg = $this->getSubdomainLang();
		$server = $this->getServerHref();
		if($lg)
			$server = substr($server,strlen($lg)+1);
		if($sub)
			$sub .= '.';
		return $this->getProtocolHref().$sub.$server.$this->getPortHref().'/'.$this->getSuffixHref();
	}
	function getSubdomainLang($domain=null){
		if(!isset($domain))
			$domain = $this->getServerHref();
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
	function getLocation(){
		return $this->getBaseHref().ltrim($this->server['REQUEST_URI'],'/');
	}
	function set(){
		$this->handle();
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
		$this->handle();
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
}