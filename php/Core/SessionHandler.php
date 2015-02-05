<?php namespace Surikat\Core;
use Surikat\Core\ExceptionSecurity;
use Surikat\Core\Session;
use SessionHandlerInterface;
class SessionHandler implements SessionHandlerInterface{
	private $write;
	private $savePath;
	static $maxNoConnectionTime = 172800; //2 days
	static $maxNoConnectionTimePrefixed = 31536000; //1 year
	function __construct($sessionName){
		$this->open(ini_get('session.save_path'),$sessionName);
	}
	function setWrite($write){
		$this->write = (bool)$write;
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
		if(!$this->write)
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
}