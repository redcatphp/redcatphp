<?php namespace Surikat\Core;
use Surikat\Exception\Security as ExceptionSecurity;
use Surikat\User\Session;
use SessionHandlerInterface;
class SessionHandler implements SessionHandlerInterface{
	private $savePath;
	static $maxNoConnectionTime = 172800; //2 days
	static $maxNoConnectionTimePrefixed = 31536000; //1 year
	function __construct($sessionName,$savePath=null){
		if(!$savePath)
			$savePath = Session::getSavePath();
		$this->open($savePath,$sessionName);
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function open($savePath, $sessionName){
		$sessionName = str_replace('-','_',$sessionName);
		$this->savePath = $savePath.'/'.$sessionName.'_';
		return true;
	}
	function close(){
		return true;
	}
	function read($id){
		return @file_get_contents($this->savePath.$id);
	}
	function write($id, $data){
		FS::mkdir($this->savePath);
		return file_put_contents($this->savePath.$id, $data, LOCK_EX) === false ? false : true;
	}
	function destroy($id){
		$file = $this->savePath.$id;
		if(file_exists($file))
			unlink($file);
		return true;
	}
	function gc($maxlifetime){
		$check = time()-self::$maxNoConnectionTime;
		$check2 = time()-self::$maxNoConnectionTimePrefixed;
		foreach(glob($this->savePath.'*') as $file){
			if(filemtime($file)<(strpos(basename($file),'-')===false?$check:$check2)){
				@unlink($file);
			}
		}
		return true;
	}
	
	function regenerate(){
		$now = time();
		if(!isset($_SESSION['_EXPIRE_'])){
			$_SESSION['_EXPIRE_'] = $now+$this->regeneratePeriod;
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
			$_SESSION['_EXPIRE_'] = $now+$this->regeneratePeriod;
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
}