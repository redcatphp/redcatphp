<?php namespace Surikat\Component\Templator\MarkupX;
class TmlVars extends \Surikat\Component\Templator\Tml{
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
			$var = var_export($this->Template->includeVars($file,$this->Template->get()),true);
		}
		else{
			$var = '$this->includeVars("'.str_replace('"','\"',$file).'",get_defined_vars())';
		}
		$head = '<?php ';
		if(!$this->selfClosed){
			$head .= '$__localVariables=get_defined_vars();';
			$this->foot('<?php extract($__localVariables,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
		}
		$head .= 'extract('.$var.',EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>';
		$this->head($head);
	}
}