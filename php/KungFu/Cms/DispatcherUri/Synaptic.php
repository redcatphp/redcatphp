<?php namespace KungFu\Cms\DispatcherUri;
use Unit\Dispatcher\Uri as Dispatcher_Uri;
class Synaptic extends Dispatcher_Uri{
	protected $pathFS;
	function __construct($pathFS=''){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
	}
	function __invoke(){
		list($filename,$file) = func_get_args();
		$this->Unit_File_Synaptic->appendDir('Surikat');
		$this->Unit_File_Synaptic->load($this->pathFS.$file);
	}
}