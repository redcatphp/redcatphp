<?php namespace Surikat\Component\Templator;
class TML_Vars extends TML{
	protected $hiddenWrap = true;
	function load(){
		$this->remapAttr('file');
		$prefix = $this->__get('prefix');
		if(!isset($prefix))
			$prefix = 'vars.';
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.php';
		$file = $prefix.$file;
		$file = $this->Template->find($file);
		if(!$file)
			return;
		if($this->__get('static')){
			$var = var_export(include($file),true);
		}
		else{
			$var = 'include("'.str_replace('"','\"',$file).'")';
		}
		$this->head('<?php $__localVariables=compact(array_keys(get_defined_vars()));'.
					'extract('.$var.',EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>');
		if(!$this->selfClosed)
			$this->foot('<?php extract($__localVariables,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
	}
}
