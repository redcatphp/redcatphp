<?php namespace Surikat\Presenter;
use Surikat\Tool\ArrayObject;
use Surikat\View\FILE;
use Surikat\View\TML;
use Surikat\Controller\Application;
class Basic extends ArrayObject{
	static function load(TML $tml){
		if(!$tml->TeMpLate)
			return;
		$c = get_called_class();
		$o = new $c();
		$o->merge([
			'templatePath'		=> $tml->TeMpLate?$tml->TeMpLate->path:'',
			'presentAttributes'	=> $tml->getAttributes(),
			'presentNamespaces'	=> $tml->_namespaces,
		]);
		$o->setView($tml->TeMpLate);
		$o->assign();
		$head = '<?php if(isset($THIS))$_THIS=$THIS;$THIS=new '.$c.'('.var_export($o->getArray(),true).');';
		$head .= '$THIS->setView($this);';
		$head .= '$THIS->execute();';
		$head .= 'extract((array)$THIS,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>';
		$tml->head($head);
		if(!empty($tml->childNodes))
			$tml->foot('<?php if(isset($_THIS));extract((array)($THIS=$_THIS),EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
		$tml->TeMpLate->present = $o;
	}
	protected $View;
	function setView($View){
		$this->View = $View;
	}
	function assign(){}
	function execute(){	
		if(isset($this->presentAttributes->uri)&&$this->presentAttributes->uri=='static'&&(($this->URI&&count($this->URI->getParams())>1)||!empty($_GET)))
			(new Application())->error(404);
		$this->dynamic();
	}
	function dynamic(){}
}