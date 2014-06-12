<?php namespace surikat;
use surikat\control\ArrayObject;
use surikat\control\PHP;
use surikat\control\HTTP;
use surikat\view\FILE;
use surikat\view\TML;
class present extends ArrayObject{
	static function document(FILE $file){}
	static function load(TML $tml){
		$ns = $tml->namespace.':'.$tml->namespaceClass;
		$file = $tml->vFile;
		if($file->present&&strpos($file->present->namespace.':'.$file->present->namespaceClass,$ns)===0)
			return; //avoid collision
		$file->present = $tml;
		$c = get_called_class();
		$o = new $c();
		$o->merge(array(
			'templatePath'		=> $file->path,
			'presentAttributes'	=> $tml->attributes,
			'presentNamespaces'	=> explode(':',$ns),
		));
		$o->assign();
		$code = '<?php $o=new '.$c.'('.var_export($o->getArray(),true).');';
		$code .= '$o->execute();';
		$code .= 'extract((array)$o);?>';
		//print('<pre>'.htmlentities($code).'</pre>');exit;
		$tml->head($code);
		unset($o);
	}
	function assign(){}
	function execute(){
		 if(isset($this->presentAttributes->uri)&&$this->presentAttributes->uri=='static'&&(count(view::param())>1||!empty($_GET)))
			view::error(404);
		$this->dynamic();
	}
	function dynamic(){}
}
