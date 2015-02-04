<?php namespace Surikat\Core;
use Surikat\Core\ExceptionSecurity;
use Surikat\Core\Session;
use SessionHandlerInterface;
class SessionHandler implements SessionHandlerInterface{
	private $fake;
	private $savePath;
	static $maxNoConnectionTime = 172800; //2 days
	static $maxNoConnectionTimePrefixed = 31536000; //1 year
	function __construct($sessionName){
		$this->open(ini_get('session.save_path'),$sessionName);
	}
	function destroyKey($key){
		foreach(glob($this->savePath.$key.'.*') as $file)
			@unlink($file);
	}
	function open($savePath, $sessionName){
		$sessionName = str_replace('.','-',$sessionName);
		$this->savePath = $savePath.'/'.$sessionName.'_';
		return true;
	}
	function close(){
		return true;
	}
	function checkBlocked(){
		if($s=Session::isBlocked()){
			Session::removeCookie();
			$this->fake = true;
			throw new ExceptionSecurity(sprintf('Too many failed session open or login submit. Are you trying to bruteforce me ? Wait for %d seconds',$s));
		}
	}
	function read($id){
		$file = $this->savePath.$id;
		if(strpos($id,'.')!==false){
			$this->checkBlocked();
			if(is_file($file)){
				return file_get_contents($file);
			}
			else{
				Session::addAttempt();
				$this->checkBlocked();
			}
		}
		elseif(is_file($file)){
			return file_get_contents($file);
		}
	}
	function write($id, $data){
		if(!$this->fake)
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
			if(filemtime($file)<(strpos(basename($file),'.')===false?$check:$check2)){
				@unlink($file);
			}
		}
		return true;
	}
}