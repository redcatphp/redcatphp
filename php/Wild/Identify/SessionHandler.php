<?php namespace Wild\Identify;
class SessionHandler implements SessionHandlerInterface{
	protected $name;
	protected $savePath;
	function open($savePath, $sessionName){
        $this->savePath = $savePath;
        if(!is_dir($this->savePath)){
            @mkdir($this->savePath, 0777, true);
        }
        return true;
    }
	function read($id){
		if(is_file($this->savePath.$id))
			return file_get_contents($this->savePath.$id);
	}
	function close(){
        return true;
    }
	function write($id,$data){
		return file_put_contents($this->savePath.$id, $data, LOCK_EX) === false ? false : true;
	}
	function touch($id){
		if(is_file($this->savePath.$id))
			return touch($this->savePath.$id);
	}
	function destroy($id){
		$file = $this->savePath.$id;
		if(is_file($file))
			unlink($file);
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
}