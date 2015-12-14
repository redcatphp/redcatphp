<?php
namespace RedCat\Templix\MarkupX;
class Vars extends \RedCat\Templix\Markup{
	protected $hiddenWrap = true;
	function load(){
		if(!$this->templix)
			return;
		$this->remapAttr('file');
		$prefix = $this->__get('prefix');
		if(!isset($prefix))
			$prefix = 'vars.';
		$file = $this->__get('file');
		if(!pathinfo($file,PATHINFO_EXTENSION))
			$file .= '.php';
		$file = $prefix.$file;
		$file = $this->templix->findPath($file);
		if(!$file)
			return;
		if($this->__get('static')){
			$var = var_export($this->templix->includeVars($file,$this->templix->get()),true);
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