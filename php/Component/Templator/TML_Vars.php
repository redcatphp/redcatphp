<?php namespace Surikat\Component\Templator;
class TML_Vars extends TML{
	protected $hiddenWrap = true;
	function load(){
		/*
		$o->timeCompiled = time();
		$o->assign();
		$head = '<?php if(isset($THIS))$_THIS=$THIS;$THIS=new '.$c.'('.var_export($o->getArray(),true).');';
		$head .= '$THIS->setView($this);';
		$head .= '$THIS->execute();';
		$head .= 'extract((array)$THIS,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>';
		$tml->head($head);
		if(!empty($tml->childNodes))
			$tml->foot('<?php if(isset($_THIS));extract((array)($THIS=$_THIS),EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
		*/
	}
}
