<?php namespace Surikat\Core;
use Surikat\Core\Session;
class SessionHandler{
	private $savePath;
	static $maxNoConnectionTime = 172800; //deux jours
	function destroyKey($key){
		foreach(glob($this->savePath.'/'.$key.'.*') as $file)
			@unlink($file);
	}
    function open($savePath, $sessionName){
        $this->savePath = $savePath;
        return true;
    }
    function close(){
        return true;
    }
    function read($id){
		$file = $this->savePath.'/'.$id;
		if(is_file($file)){
			return file_get_contents($file);
		}
		else{
			Session::addAttempt();
		}
    }
    function write($id, $data){
        return file_put_contents($this->savePath.'/'.$id, $data, LOCK_EX) === false ? false : true;
    }
    function destroy($id){
        $file = $this->savePath.'/'.$id;
        if(file_exists($file))
			unlink($file);
        return true;
    }
    function gc($maxlifetime){
		$maxlifetime = self::$maxNoConnectionTime;
        foreach(glob($this->savePath.'/*') as $file)
			if(filemtime($file)+$maxlifetime<time())
				@unlink($file);
        return true;
    }
}
