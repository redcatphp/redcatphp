<?php namespace KungFu\Cms\Dispatcher;
use Unit\Dispatcher;
use Unit\File\Synaptic as File_Synaptic;
class Synaptic extends Dispatcher{
	protected $pathFS;
	function __construct($pathFS=''){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
	}
	function __invoke(){
		list($filename,$file) = func_get_args();
		$synaptic = new File_Synaptic();
		$synaptic->appendDir('Surikat');
		$synaptic->load($this->pathFS.$file);
	}
}