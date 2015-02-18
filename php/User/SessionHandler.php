<?php namespace Surikat\User;
use Surikat\User\Session;
use Surikat\User\SessionHandlerInterface;
use Surikat\Dependency\Injector;
class SessionHandler implements SessionHandlerInterface{
	use Injector;
	protected $data = [];
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
		$this->open($savePath,$sessionName);
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function setKey($skey=null){
		$this->destroyKey($key);
		$this->key = $key;
	}
	function open($savePath, $sessionName){
		$this->autoRegenerateId();
		$this->savePath = $savePath.'/'.$sessionName.'/';
		return true;
	}
	function close(){
		return true;
	}
	function read($id){
		return @file_get_contents($this->savePath.$this->key.$this->splitter.$id);
	}
	function write($id, $data){
		if(!is_dir($this->savePath))
			@mkdir($this->savePath,0755,true);
		return file_put_contents($this->savePath.$this->key.$this->splitter.$id, $data, LOCK_EX) === false ? false : true;
	}
	function destroy($id){
		$file = $this->savePath.$this->key.$this->splitter.$id;
		if(file_exists($file))
			unlink($file);
		return true;
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
	
	function generateId(){
		
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
}