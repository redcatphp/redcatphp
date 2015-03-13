<?php namespace Surikat\Presenter;
use Surikat\Vars\ArrayObject;
use Surikat\Templator\TML;
use Surikat\DependencyInjection\MutatorCall;
class Presenter extends ArrayObject{
	use MutatorCall;
	static function load(TML $tml){
		if(!$tml->Template)
			return;
		$tml->remapAttr('uri');
		$c = get_called_class();
		$o = new $c();
		$o->merge([
			'templatePath'		=> $tml->Template?$tml->Template->path:'',
			'presentAttributes'	=> $tml->getAttributes(),
			'presentNamespaces'	=> $tml->_namespaces,
		]);
		$o->setView($tml->Template);
		$o->timeCompiled = time();
		$o->assign();
		$head = '<?php if(isset($THIS))$_THIS=$THIS;$THIS=new '.$c.'('.var_export($o->getArray(),true).');';
		$head .= '$THIS->setView($this);';
		$head .= '$THIS->execute();';
		$head .= 'extract((array)$THIS,EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\');?>';
		$tml->head($head);
		if(!empty($tml->childNodes))
			$tml->foot('<?php if(isset($_THIS));extract((array)($THIS=$_THIS),EXTR_OVERWRITE|EXTR_PREFIX_INVALID,\'i\'); ?>');
		$tml->Template->present = $o;
	}
	protected $View;
	function setView($View){
		$this->View = $View;
	}
	function getView(){
		return $this->View;
	}
	function assign(){}
	function execute(){	
		if(isset($this->presentAttributes->uri)&&$this->presentAttributes->uri=='static'){
			if(!empty($_GET)){
				$this->notFound();
			}
			elseif(($r = $this->getView())&&($r = $r->getController())&&($r = $r->getRouter())){
				if((method_exists($r,'getParams'))&&count($r->getParams())>1){
					$this->notFound();
				}
				//elseif(0){
					//$this->haveParameters();
				//}
				//var_dump($_SERVER['PATH_INFO'],$_GET,$r->getPath());
			}
		}
		$this->time = time();
		$this->BASE_HREF = $this->HTTP_URL()->getBaseHref();
		$this->dynamic();
	}
	function dynamic(){}
	function notFound(){
		$this->Dispatcher_Index()->getController()->error(404);
	}
}