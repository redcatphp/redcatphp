<?php namespace Surikat\Dispatcher;
class Synaptic extends Dispatcher{
	protected $pathFS;
	function __construct($pathFS=''){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
	}
	function __invoke($filename,$file){
		$this->FileSystem_Synaptic->load($this->pathFS.$file);
	}
}