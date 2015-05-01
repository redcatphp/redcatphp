<?php namespace Config;
use Vars\Arrays;
class Loader {
	protected $directory = 'config';
	protected $extension = '.php';
	protected $file;
	function __construct($f='global',$dirs=null,$extension=null){
		$this->file = str_replace('_','.',$f);
		if(isset($extension)){
			$this->extension = $extension;
		}
		if(!isset($dirs))
			$dirs = [
				SURIKAT_PATH.$this->directory.'/',
				SURIKAT_SPATH.$this->directory.'/',
			];
		$this->dirs = $dirs;
	}
	function loadObject(){
		return new Config($this->load(),true,$this);
	}
	function load(){
		$array = [];
		foreach($this->dirs as $d){
			if(is_file($inc=$d.$this->file.$this->extension)){
				$conf = $this->getConf($inc);
				if(is_array($conf)){
					$array = Arrays::merge_recursive($conf,$array);
				}
			}
		}
		return $array;
	}
	function putContents($contents){
		return file_put_contents(SURIKAT_PATH.$this->directory.'/'.$this->file,$this->toString($contents));
	}
	function toString($contents){
		return '<?php return '.var_export($contents,true).';';
	}
	protected function getConf($inc){
		return include($inc);
	}
}