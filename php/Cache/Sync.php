<?php namespace Cache;
use DependencyInjection\FacadeTrait;
class Sync{
	use FacadeTrait;
	var $timespace = 'sync/timespace/';
	var $cache = 'sync/cache/';
	var $ext = '.sync';
	var $spaceName = 'sync';
	var $className;
	function mtime($file,$sync,$forceCache=true){
		$syncF = SURIKAT_TMP.$this->timespace.$sync.$this->ext;
		if($forceCache&&!is_file($syncF)){
			@mkdir(dirname($syncF),0777,true);
			file_put_contents($syncF,'');
		}
		return @filemtime($file)<@filemtime($syncF);
	}
	function update($sync){
		$syncF = SURIKAT_TMP.$this->timespace.$sync.$this->ext;
		if(!is_file($syncF)){
			@mkdir(dirname($syncF),0777,true);
			file_put_contents($syncF,'');
		}
		else
			touch($syncF);
	}
	function __call($c,$args){
		$id = sha1(serialize([$c,$args]));
		$file = SURIKAT_TMP.$this->cache.$this->spaceName.'/'.$id;
		if(strpos($c,'static')===0&&ctype_upper(substr($c,6,1)))
			return $this->_statical(lcfirst(substr($c,6)),$args,$id,$file);
		elseif(strpos($c,'sync')===0&&ctype_upper(substr($c,4,1)))
			return $this->_sync(lcfirst(substr($c,4)),$args,$id,$file);
		else
			return $this->_dynTry($c,$args,$id,$file);
	}
	private function _dynTry($c,$args,$id,$file){
		$data = null;
		try{
			@mkdir(dirname($file),0777,true);
			file_put_contents($file,serialize($data=call_user_func_array([($this->className?$this->className:$this->spaceName),$c],$args)),LOCK_EX);
		}
		catch(\PDOException $e){
			$data = unserialize(file_get_contents($file));
		}
		return $data;
	}
	private function _sync($c,$args,$id,$file){
		$sync = SURIKAT_TMP.$this->timespace.$this->spaceName.'.'.$args[0].$this->ext;
		if(!($msync=@filemtime($sync))||@filemtime($file)<$msync)
			return $this->_dynTry($c,$args,$id,$file);
		return unserialize(file_get_contents($file));
	}
	private function _statical($c,$args,$id,$file){
		if(!file_exists($file))
			return $this->_dynTry($c,$args,$id,$file);
		else
			return unserialize(file_get_contents($file));
	}
}