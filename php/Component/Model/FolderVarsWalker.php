<?php namespace Surikat\Model;
use Surikat\Model\FolderVars;
class FolderVarsWalker{
	protected $document = null;
	protected $dirs = [];
	function __call($func,$args){
		$r = [];
		foreach($this->dirs as $k=>$o){
			if(isset($this->dirs[$k])){
				$r[$k] = call_user_func_array([$this->dirs[$k],$func],$args);
			}
		}
		return $r;
	}
	function __set($key,$val){
		foreach($this->dirs as $o){
			$o->$key = $val;
		}
	}
	function __get($key){
		$r = [];
		foreach($this->dirs as $k=>$o){
			$r[$k] = $o->$key;
		}
		return $r;
	}
	function __construct(FolderVars &$o){
		$this->document = &$o;
		foreach($this->document->lsdir() as $basename){
			$k = pathinfo($basename,PATHINFO_FILENAME);
			$this->dirs[$k] = FolderVars::factory($this->document->key.$k);
		}
	}
}