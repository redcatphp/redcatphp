<?php namespace Surikat;
use Surikat\Control\ArrayObject;
use Surikat\Control\PHP;
use Surikat\Control\HTTP;
use Surikat\View\FILE;
use Surikat\View\TML;
class Present extends ArrayObject{
	static function document(FILE $file){}
	static function load(TML $tml){
		if(!$tml->vFile)
			return;
		$c = get_called_class();
		$o = new $c();
		$o->merge([
			'templatePath'		=> $tml->vFile?$tml->vFile->path:'',
			'presentAttributes'	=> $tml->getAttributes(),
			'presentNamespaces'	=> $tml->_namespaces,
		]);
		$o->assign();
		$head = '<?php if(isset($THIS))$_THIS=$THIS;$THIS=new '.$c.'('.var_export($o->getArray(),true).');';
		$head .= '$THIS->execute();';
		$head .= 'extract((array)$THIS,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>';
		$tml->head($head);
		if(!empty($tml->childNodes))
			$tml->foot('<?php if(isset($_THIS));extract((array)($THIS=$_THIS),EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
		$tml->vFile->present = $o;
	}
	function assign(){}
	function execute(){
		 if(isset($this->presentAttributes->uri)&&$this->presentAttributes->uri=='static'&&(count(view::getInstance()->getUri()->getParam())>1||!empty($_GET)))
			view::getInstance()->error(404);
		$this->dynamic();
	}
	function dynamic(){}
}