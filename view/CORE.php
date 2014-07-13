<?php namespace surikat\view;
use surikat\control;
use surikat\view;
use surikat\view\FILE;
use surikat\view\TML;
use surikat\view\TML_Apply;
use surikat\view\CssSelector\CssSelector;
use surikat\view\CssSelector\Parser\CssParserHelper;
# Nodal Representation for Templating-Markup-Language
# 	Pure Object Recursive Nodal Dom for XML/XHTML/HTML5/PHP5
class CORE extends PARSER implements \ArrayAccess,\IteratorAggregate{
	var $nodeName;
	var $parent;
	var $constructor;
	var $childNodes = array();
	var $attributes = array();
	var $metaAttribution = array();
	var $previousSibling;
	var $nextSibling;
	var $namespace;
	var $namespaceClass;
	var $_namespaces;
	var $vFile;
	private $selectorService;
	protected $hiddenWrap;
	protected $preventLoad;
	protected $selfClosed;
	protected $noParseContent;
	function evalue(){
		return ob_start()&&eval('?>'.$this)!==false?ob_get_clean():'';
	}
	function recursiveMethod($callback,$node=null,$args=null){
		if(func_num_args()<2)
			$node = &$this;
		call_user_func_array(array(&$node,$callback),(array)$args);
		foreach($node->childNodes as $el)
			$this->recursiveMethod($callback,$el,$args);
	}
	function recursive($callback,$node=null,$break=false){
		if(func_num_args()<2)
			$node = &$this;
		call_user_func_array($callback,array(&$node,&$break));
		if($break)
			return;
		foreach($node->childNodes as $el){
			$this->recursive($callback,$el,$break);
			if($break)
				break;
		}
	}
	function arecursive($callback,$node=null){
		if(func_num_args()<2)
			$node = &$this;
		foreach($node->childNodes as $el)
			$this->arecursive($callback,$el);
		call_user_func_array($callback,array(&$node));
	}
	function vFileOf($file){
		return FILE::factoy(dirname($this->vFile->path).'/'.$file);
	}
	function pathFile($file){
		return $this->vFileOf($file)->path($file);
	}
	function getFile($file,$c=null){
		$vFile = $this->vFileOf($file);
		if(!is_file($real=$vFile->path($file)))
			throw new Exception_TML('Template '.$c.': "'.$file.'" not found called in "'.$this->vFile->dirCwd.'" by "'.$vFile->path.'"');
		return file_get_contents($real);
	}
	function parseFile($file,$params=null,$c=null){
		return $this->parse($this->getFile($file,$c),$params);
	}
	function getInnerTml(){
		$str = '';
		foreach($this->childNodes as $c)
			$str .= $c;
		return $str;
	}
	function __construct(){
		$args = func_get_args();
		if(!empty($args)){
			if(is_string($args[0])){
				$parse = array_shift($args);
				$o = array_shift($args);
				if($o instanceof CORE)
					$this->parent = $o;
				elseif(is_array($o)){
					$this->vFile = array_shift($o);
					$this->constructor = array_shift($o);
				}
				else{
					$this->vFile = $o;
					$this->constructor = array_shift($args);
				}
				if($this->parent)
					$this->vFile = $this->parent->vFile;
				$this->parse($parse);
			}
			else{
				$this->parent = array_shift($args);
				if($this->parent)
					$this->vFile = $this->parent->vFile;
				$this->interpret($args);
			}
		}
		if($this->parent&&($prev=end($this->parent->childNodes))){
			$prev->nextSibling = &$this;
			$this->previousSibling = &$prev;
		}
	}
	protected function cacheForge($extra=null,$php=true,$ev=false){
		$code = "$this";
		$h = sha1($code);
		if($php)
			$this->vFile->cachePHP($h,'<?php ob_start();?>'.$code.'<?php $this->cacheRegen(__FILE__,ob_get_clean());',true);
		if($ev)
			$this->vFile->cacheV($h,$this->evalue());
		$this->clear();
		$this->head('<?php if($__including=$this->cacheInc(\''.$h.'\''.($extra!==null?(','.(is_string($extra)?"'".str_replace("'","\'",$extra)."'":'unserialize('.serialize($extra).')')):'').'))include $__including;?>');
	}
	protected static function varExport($var,$singlequote=null){
		if(is_array($var)){
			$toImplode = array();
			foreach($var as $key=>$value)
				$toImplode[] = self::varExport($key,$singlequote).'=>'.self::varExport($value,$singlequote);
			$code = 'array('.implode(',', $toImplode).')';
			return $code;
		}
		$var = var_export($var,true);
		if(!$singlequote&&strpos($var,"'")===0&&strrpos($var,"'")===strlen($var)-1)
			$var = '"'.str_replace('"','\\"',stripslashes(substr($var,1,-1))).'"';
		return $var;
	}
	protected static function exportVars(){
		$args = array();
		foreach(func_get_args() as $arg)
			$args[] = self::varExport($arg);
		return implode(',',$args);
	}
	public static function triggerExec($str){
		$c = get_called_class();
		$o = new $c();
		$o->preventLoad = true;
		$o->parse($str);
		return $o->onExec();
	}
	function onLoad($callback,$unshift=null){
		if($unshift)
			array_unshift($this->onLoad,$callback);
		else
			array_push($this->onLoad,$callback);
	}
	function onLoaded($callback,$unshift=null){
		if($unshift)
			array_unshift($this->onLoaded,$callback);
		else
			array_push($this->onLoaded,$callback);
	}
	function load(){}
	function loaded(){}
	function triggerLoaded(){
		foreach($this->onLoaded as $callback)
			if(is_callable($callback))
				call_user_func($callback);
		if($this->preventLoad)
			return;
		foreach($this->childNodes as $c)
			$c->triggerLoaded();
		$this->preventLoad = true;
		if($this->_namespaces){
			$x = $this->_namespaces;
			while($v=array_pop($x)){
				if(class_exists($c=(($s=implode('\\',$x))?$s.'\\':'').$v)&&method_exists($c,'loaded')){
					$c::loaded($this);
					break;
				}
			}
		}
		$this->loaded();
		foreach(array_keys($this->metaAttribution) as $k){
			$key = is_integer($k)?$this->metaAttribution[$k]:$k;
			if(method_exists($this,$m='loaded'.ucfirst(str_replace('-','_',$k)))||(($pos=strpos($k,'-'))!==false&&method_exists($this,$m='loaded'.ucfirst(substr($k,0,$pos).'_'))&&($key=substr($k,$pos+1))))
				$this->$m($this->metaAttribution[$k],$key);
		}
	}
	protected function opened(){
		if((isset($this->metaAttribution['/'])&&$i='/')||(($i=array_search('/',$this->metaAttribution))!==false&&is_integer($i))){
			$this->selfClosed = 2;
			unset($this->metaAttribution[$i]);
		}
		foreach(array_keys($this->metaAttribution) as $k){
			if(self::checkPIOC($this->metaAttribution[$k])){
				$this->metaAttribution[$k] = new PHP($this,null,$this->metaAttribution[$k],$this);
				if(!is_integer($k))
					$this->attributes[$k] = &$this->metaAttribution[$k];
			}
			elseif(self::checkPIOC($k)){
				$v = $this->metaAttribution[$k];
				unset($this->metaAttribution[$k]);
				$this->metaAttribution[] = new PHP($this,null,$k.'="'.$v.'"',$this);
			}
			elseif(!is_integer($k))
				$this->attributes[$k] = &$this->metaAttribution[$k];		
			else
				$this->attributes[$this->metaAttribution[$k]] = &$this->metaAttribution[$k];
		}
	}
	protected function closed(){
		foreach($this->onLoad as $callback)
			if(is_callable($callback))
				call_user_func($callback);
		if($this->preventLoad)
			return;
		foreach(array_keys($this->metaAttribution) as $k){
			$key = is_integer($k)?$this->metaAttribution[$k]:$k;
			if((method_exists($this,$m='load'.ucfirst(str_replace('-','_',$k)))||(($pos=strpos($k,'-'))!==false&&method_exists($this,$m='load'.ucfirst(substr($k,0,$pos).'_'))&&($key=substr($k,$pos+1)))))
				$this->$m($this->attributes[$k],$key);
		}
		if(!$this->preventLoad){
			if($this->_namespaces){
				$x = $this->_namespaces;
				while($v=array_pop($x)){
					if(class_exists($c=(($s=implode('\\',$x))?$s.'\\':'').$v)&&method_exists($c,'load')){
						$c::load($this);
						break;
					}
				}
			}
			$this->load();
		}
		if(method_exists($this,'onExec')){
			$this->head('<?php ob_start();?>');
			$this->foot('<?php echo '.get_class($this).'::triggerExec(ob_get_clean());?>');
		}
	}
	
	function __invoke(){
		return call_user_func_array(array($this,'find'),func_get_args());
	}
	function offsetUnset($k){
		unset($this->childNodes[$k]);
	}
	function offsetGet($k){
		return isset($this->childNodes[$k])?$this->childNodes[$k]:null;
	}
	function offsetSet($k,$v){
		$this->append($v,$k);
	}
	//function offsetExists($k){
		//return isset($this->childNodes[$k]);
	//}
	function offsetExists($k){
		return !!$this($k);
	}
	function getIterator(){
		return $this->childNodes;
	}
	function __unset($k){
		if(isset($this->metaAttribution[$k]))
			unset($this->metaAttribution[$k]);
		elseif(($i=array_search($k,$this->metaAttribution))!==false)
			unset($this->metaAttribution[$i]);
		else
			$this->attributes[$k] = null;
		unset($this->attributes[$k]);
	}
	function __isset($k){
		return isset($this->attributes[$k]);
	}
	function __get($k){
		return isset($this->attributes[$k])?$this->attributes[$k]:null;
	}
	function __set($k,$v){
		if($k===null)
			$this->metaAttribution[] = $v;
		else{
			$this->metaAttribution[$k] = $v;
			$this->attributes[$k] = &$this->metaAttribution[$k];
		}
	}
	function wrapCode($head,$foot=null){
		if($foot===true){
			$head = '<?php '.$head.'{?>';
			$foot = '<?php }?>';
		}
		$this->head($head);
		if($foot!==null)
			$this->foot($foot);
	}
	function wrapInnerCode($head,$foot=null){
		if($foot===true){
			$head = '<?php '.$head.'{?>';
			$foot = '<?php }?>';
		}
		$this->innerHead($head);
		if($foot!==null)
			$this->innerFoot($foot);
	}
	function attrFind($opts,$sdef=null,$unset=null){
		foreach($opts as $k)
			if(isset($this->attributes[$k])){
				$v = $this->attributes[$k];
				if($unset)
					unset($this->attributes[$k]);
				return $v;
			}
		if($sdef!==null)
			return $sdef;
	}
	function attrFinderUnset(){
		return $this->attrFind(func_get_args(),func_get_arg(0),true);
	}
	function attrFinder(){
		return $this->attrFind(func_get_args(),func_get_arg(0));
	}

	function selector(){
		if(!func_num_args())
			return isset($this->selectorService)?$this->selectorService:($this->selectorService=new CssSelector($this));
		else
			return $this->selector()->query(func_get_arg(0));
	}
	function closest($selector=null){
		$ref = &$this;
		if($selector===null){
			while($ref->parent)
				$ref = &$ref->parent;
			return $ref;
		}
		else
			while($ref->parent){
				$ref = &$ref->parent;
				if($ref->match($selector))
					return $ref;
			}
	}
	function match($selector){
		$ABS = new ABSTRACTION($this->nodeName,$this->attributes);
		$c = count($ABS->selector($selector));
		unset($ABS);
		return $c;
	}
	function find($selector=null,$index=null){
		if(is_array($selector)){
			$inv = $this;
			foreach($selector as $select){
				$r = array();
				foreach($inv($select) as $o)
					$r[] = $o;
			}
		}
		elseif($selector=='*'||$selector===null){
			$r = $this->childNodes;
		}
		else{
			$r = array();
			foreach($this->childNodes  as $el)
				$r = array_merge($r,(array)$el->selector($selector));
		}
		if($index===true)
			return new Iterator($r);
		elseif($index!==null)
			return isset($r[$index])?$r[$index]:null;
		return $r;
	}
	function submerge($node){
		if(is_scalar($node))
			$node = new TML($node,$this);
		foreach($node->childNodes as $n)
			$this->merge($n);
	}
	function isSameNode($node){
		return $node->childNodes===$this->childNodes
			&&$node->metaAttribution==$this->metaAttribution
			&&$node->nodeName==$this->nodeName
			&&get_class($node)==get_class($this);
	}
	function getAttributes(){
		$a = array();
		foreach($this->metaAttribution as $k=>$v)
			$a[$k] = (string)$v;
		return $a;
	}
	function getAttribute($k){
		return $this->attr($k);
	}
	function hasAttribute($k){
		return isset($this->attributes[$k]);
	}
	function getElementsByTagName($nodeName){
		$a = array();
		$this->recursive(function($el)use(&$a,$nodeName){
			if($el->nodeName&&($nodeName=='*'||$el->nodeName==$nodeName))
				$a[] = $el;
		});
		return $a;
	}
	
	function write($append){
		$this->clearInner();
		$this->append($append);
	}
	function append($v,$k=null){
		if(is_scalar($v))
			$v = new TML($v,$this);
		if($k===null)
			$this->childNodes[] = $v;
		else
			$this->childNodes[$k] = $v;
		return $v;
	}
	function prepend($v){
		if(is_scalar($v))
			$v = new TML($v,$this);
		array_unshift($this->childNodes,$v);
		return $v;
	}
	function _merging($args,$callback){
		foreach($args as $nodes){
			if($nodes instanceof Iterator)
				$nodes = $nodes->getIterator();
			if(!is_array($nodes))
				$nodes = array($nodes);
			foreach($nodes as $node){
				if(is_scalar($node))
					$node = new TML($node,$this);
				$found = false;
				foreach($this->childNodes as $n)
					if($n->isSameNode($node)){
						$found = true;
						break;
					}
				if(!$found)
					$callback($this->childNodes,$node);
			}
		}
	}
	function merge(){
		return $this->_merging(func_get_args(),'array_push');
	}
	function premerge(){
		return $this->_merging(func_get_args(),'array_unshift');
	}
	function each($v){
		return call_user_func($v,$this);
	}
	function replaceWith($obj){
		if(is_scalar($obj))
			$obj = new TML($obj,$this->parent);
		if(!$this->parent){
			$this->clean();
			$this[] = $obj;
		}
		else{
			foreach($this->parent->childNodes as $i=>$child)
				if($child===$this){
					$this->parent->childNodes[$i] = $obj;
					$obj->parent = $this->parent;
					break;
				}
		}
		return $obj;
	}
	function applyFile($tpl,$params=array()){
		if(($pos=strpos($tpl,':'))!==false)
			$tpl = '../'.substr($tpl,0,$pos).'/'.($base=substr($tpl,$pos+1)).(strpos($base,'/')===false?'/'.$base:'').'.tpl';
		TML_Apply::manualLoad($tpl,$this,$params);
	}
	function before($arg){
		if(is_scalar($arg))
			$arg = new TML($arg,$this->parent);
		array_splice($this->parent->childNodes, $this->getIndex()-1, 0, array($arg));
		return $arg;
	}
	function after($arg){
		if(is_scalar($arg))
			$arg = new TML($arg,$this->parent);
		array_splice($this->parent->childNodes, $this->getIndex()+1, 0, array($arg));
		return $arg;
	}
	function getIndex(){
		foreach($this->parent->childNodes as $i=>$child)
			if($child===$this)
				return $i;
	}

	private function indentationIndex(){
		return $this->parent?$this->parent->indentationIndex()+($this->nodeName&&!$this->hiddenWrap?1:0):0;
	}
	private function indentationTab(){
		if(control::devHas(control::dev_view)&&!$this instanceof PHP&&$this->nodeName&&!$this->hiddenWrap)
			return "\n".str_repeat("  ",$this->indentationIndex());
	}
	function getInner(){
		return implode('',$this->innerHead).implode('',$this->childNodes).implode('',$this->innerFoot);
	}
	function __toString(){
		$str = $this->indentationTab();
		$str .= implode('',$this->head);
		if(!$this->hiddenWrap){
			$str .= '<'.$this->nodeName;
			foreach($this->metaAttribution as $k=>$v)
				if(is_integer($k)){
					if($this->vFile->isXhtml&&isset($this->attributes[$v])&&$v==$this->attributes[$v])
						$str .= ' '.$v.'="'.$v.'"';
					else
						$str .= ' '.$v;
				}
				else{
					$str .= ' '.$k.'="'.$v.'"';
				}
			if($this->selfClosed&&$this->vFile->isXhtml)
				$str .= '></'.$this->nodeName;
			elseif($this->selfClosed>1)
				$str .= ' /';
			$str .= '>';
		}
		$str .= $this->getInner();
		if(!$this->selfClosed&&!$this->hiddenWrap)
			$str .= $this->indentationTab()."</".$this->nodeName.">";
		$str .= implode('',$this->foot);
		return $str;
	}
	function clear(){
		$this->clean();
		$this->clearInner();
	}
	function clearInner(){
		$this->innerHead = array();
		$this->innerFoot = array();
		$this->childNodes = array();
	}
	function clean(){
		$this->clearInner();
		$this->hiddenWrap = true;
		$this->head = array();
		$this->foot = array();
	}
	function delete(){
		if($this->nextSibling)
			$this->nextSibling->previousSibling = $this->previousSibling;
		if($this->previousSibling)
			$this->previousSibling->nextSibling = $this->nextSibling;
		$this->parent = null;
		$this->nodeName = null;
		$this->previousSibling = null;
		$this->nextSibling = null;
		$this->childNodes = array();
		$this->attributes = array();
		$this->hiddenWrap = true;
		$this->innerHead = array();
		$this->innerFoot = array();
		$this->head = array();
		$this->foot = array();
	}
	
	protected $foot = array();
	protected $head = array();
	protected $innerFoot = array();
	protected $innerHead = array();
	function &head($arg){
		if(func_num_args()>1)
			array_splice($this->head, func_get_arg(1), 0,array($arg));
		else
			array_unshift($this->head,$arg);
		return $arg;
	}
	function &foot($arg){
		if(func_num_args()>1)
			array_splice($this->foot, func_get_arg(1), 0,array($arg));
		else
			array_push($this->foot,$arg);
		return $arg;
	}
	function &innerHead($arg){
		if(func_num_args()>1)
			array_splice($this->innerHead, func_get_arg(1), 0,array($arg));
		else
			array_unshift($this->innerHead,$arg);
		return $arg;
	}
	function &innerFoot($arg){
		if(func_num_args()>1)
			array_splice($this->innerFoot, func_get_arg(1), 0,array($arg));
		else
			array_push($this->innerFoot,$arg);
		return $arg;
	}

	function attr($k){
		if(func_num_args()>1)
			$this->__set($k,func_get_arg(1));
		elseif(is_array($k))
			foreach($k as $_k=>$v)
				$this->__set($_k,$v);
		else
			return $this->__get($k);
	}
	function removeAttr($k){
		if(is_array($k)){
			foreach($k as $v)
				$this->__unset($v);
		}
		else
			$this->__unset($k);
	}
	function remapAttr($to,$from=0){
		if(!isset($this->metaAttribution[$to])&&isset($this->metaAttribution[$from])){
			$this->metaAttribution[$to] = $this->metaAttribution[$from];
			unset($this->metaAttribution[$from]);
		}
	}

	private $__metaData = array();
	function data($k,$v=null){
		if(func_num_args()>1)
			$this->__metaData[$k] = $v;
		elseif(is_array($k))
			foreach($k as $_k=>$v)
				$this->__metaData[$_k] = $v;
		else
			return $this->__metaData[$k];
	}
	function setCss($k,$v){
		$x = explode(';',$this->__get('style'));
		$style = '';
		$found = false;
		foreach($x as $p){
			$xd = explode(':',$p);
			if(!$xd[0]&&!isset($xd[1]))
				continue;
			if($xd[0]==$k){
				$xd[1] = $v;
				$found = true;
			}
			$style .= $xd[0].':'.$xd[1].';';
		}
		if(!$found)
			$style .= $k.':'.$v.';';
		$this->__set('style',$style);
	}
	function getCss($k){
		if(!$this->__isset('style'))
			return;
		$x = explode(';',$this->__get('style'));
		foreach($x as $p){
			$xd = explode(':',$p);
			if($xd[0]==$k)
				return isset($xd[1])?$xd[1]:null;
		}
	}
	function css($k){
		if(func_num_args()>1)
			$this->setCss($k,func_get_arg(1));
		elseif(is_array($k))
			foreach($k as $_k=>$v)
				$this->setCss($_k,$v);
		else
			return $this->getCss($k);
	}
	function removeClass($class){
		$class = trim($class);
		if(strpos($class,' ')){
			$x = explode(' ',$class);
			foreach($x as $cl)
				$this->removeClass($cl);
		}
		elseif($this->__isset('class')&&strpos($this->__get('class'),$class)!==false)
			$this->__set('class',trim(str_replace($class,'',$this->__get('class'))));
	}
	function addClass($class){
		$class = trim($class);
		if(strpos($class,' ')){
			$x = explode(' ',$class);
			foreach($x as $cl)
				$this->addClass($cl);
		}
		elseif(!$this->__isset('class')||strpos($this->__get('class'),$class)===false)
			$this->__set('class',trim($this->__get('class').' '.$class));
	}
	function wrap($arg){
		if(is_scalar($arg)){
			$arg = new TML($arg,$this->parent);
			if(isset($arg->childNodes[0]))
				$arg = $arg->childNodes[0];
			$arg->selfClosed = null;
		}
		$arg->parent = $this->parent;
		array_splice($this->parent->childNodes, $this->getIndex(), 1, array($arg));
		$arg->childNodes[] = $this;
		$this->parent = $arg;
	}
	function unwrap($arg='*'){ #experimental - stranges result when used with iterator just wraped before
		$ref = $this->closest($arg);
		$ref->replaceWith($ref->getInner());
	}
	function replace($mix){
		$this->parse($mix);
	}

	function addJsScript($js=null){
		if(!$js)
			$js = $this;
		$dom = $this->closest()->find('body',0);
		if(!$dom)
			return;
		$src = trim($js->src?$js->src:($js->href?$js->href:key($js->attributes)));
		if($src){
			if(!($script=$dom->find('script:not([src]):last',0))){
				$dom[] = '<script type="text/javascript"></script>';
				$script = $dom->find('script:not([src]):last',0);
			}
			$sync = isset($js->sync)&&$js->sync!='false'||$js->async=='false'?',true':'';
			$app = "\$js('$src'$sync);";
			if(strpos("$script",$app)===false)
				$script->append($app);
		}
	}
	function addCssLink($css=null){
		static $path = 'css/';
		if(!$css)
			$css = $this;
		$dom = $this->closest()->find('head',0);
		if(!$dom)
			return;
		$href = trim($css->href?$css->href:($css->src?$css->src:key($css->attributes)));
		if($href&&strpos($href,'://')===false&&strpos($href,'/')!==0){
			if(strpos($href,$path)!==0)
				$href = $path.$href;
			if(substr($href,-4)!='.css')
				$href .= '.css';
		}
		$media_s = $css->media?'[media="'.$css->media.'"]':'';
		$media = $css->media?' media="'.$css->media.'"':'';
		if($href&&!($script=$dom->find('link[href="'.$href.'"]'.$media_s,0)))
			$dom[] = '<link href="'.$href.'" rel="stylesheet" type="text/css"'.$media.'>';
	}

	function presentProperty(){
		if(strpos(func_get_arg(0),'<?')!==false){
			extract((array)$this->vFile->present);
			ob_start();
			eval('?>'.func_get_arg(0));
			return ob_get_clean();
		}
		return func_get_arg(0);
	}
}