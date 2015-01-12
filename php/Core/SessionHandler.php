<?php namespace Surikat\Core;
class SessionHandler{
	private $savePath;
	static $maxNoConnectionTime = 172800; //deux jours
	function destroyKey($key){
		foreach(glob($this->savePath.'/sess_'.$key.'.*') as $file)
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
        return (string)@file_get_contents($this->savePath.'/sess_'.$id);
    }
    function write($id, $data){
        return file_put_contents($this->savePath.'/sess_'.$id, $data, LOCK_EX) === false ? false : true;
    }
    function destroy($id){
        $file = $this->savePath.'/sess_'.$id;
        if(file_exists($file))
			unlink($file);
        return true;
    }
    function gc($maxlifetime){
		$maxlifetime = self::$maxNoConnectionTime;
        foreach(glob($this->savePath.'/sess_*') as $file)
			if(filemtime($file)+$maxlifetime<time())
				@unlink($file);
        return true;
    }
}
