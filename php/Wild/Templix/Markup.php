<?php
namespace Wild\Templix;
use Wild\Templix\MarkupX\Apply;
class Markup implements \ArrayAccess,\IteratorAggregate{	
	//Parser
	const STATE_PROLOG_EXCLAMATION = 1;
	const STATE_PROLOG_DTD = 2;
	const STATE_PROLOG_INLINEDTD = 3;
	const STATE_PROLOG_COMMENT = 4;
	const STATE_PARSING = 5;
	const STATE_PARSING_COMMENT = 6;
	const STATE_NOPARSING = 7;
	const STATE_PARSING_OPENER = 8;
	const STATE_ATTR_NONE = 9;
	const STATE_ATTR_KEY = 10;
	const STATE_ATTR_VALUE = 11;
	const PIO = '*~#@?!?#+1';
	const PIC = '0+#@!?!#~*';	
	
	private static $PIO_L;
	private static $PIC_L;
	private static $PI_STR = [self::PIO,self::PIC];
	private static $PI_HEX;
	
	private $__phpSRC = [];
	private $parseReplacement = [
		'\\<'=>'&lt;',
		'\\>'=>'&gt;',
		'\\"'=>'&quot;',
	];
	private $onLoad = [];
	private $onLoaded = [];
	private $currentTag;
	private $lineNumber = 1;
	private $characterNumber = 1;
	
	//Core
	private static $loadVarsIndex = 100;
	private $selectorService;
	
	protected $hiddenWrap;
	protected $preventLoad;
	protected $selfClosed;
	protected $__closed;
	protected $noParseContent;
	protected $spaceAfterOpen;
	protected $spaceAfterClose;
	protected $foot = [];
	protected $head = [];
	protected $innerFoot = [];
	protected $innerHead = [];
	
	public $nodeName;
	public $parent;
	public $constructor;
	public $childNodes = [];
	public $attributes = [];
	public $metaAttribution = [];
	public $previousSibling;
	public $nextSibling;
	public $templix;
	
	function loaded(){
		
	}
	function load(){
	}
	function loadCacheSync($v,$k){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheSync']);
		$this->cacheForge($v);
	}
	function loadCacheStatic($v){
		$this->load();
		$this->preventLoad = true;
		unset($this->metaAttribution['cacheStatic']);
		$this->cacheForge(null,false,true);
	}
	
	function loadVars($v){
		if(!$this->templix)
			return;
		$index = uniqid();
		$this->attr('compileVars',$index);
		$this->removeAttr('vars');
		$this->templix->onCompile(function($markup)use($v,$index){
			$el = $markup->find("[compileVars=$index]",0);
			if(!$el)
				return;
			$el->removeAttr('compileVars');
			$rw = $el->getInnerMarkups();
			if(substr($rw,0,11)=='<?php echo '&&substr($rw,-3)==';?>'){
				$rw = substr($rw,11,-3);
			}
			else{
				$rw = '"'.str_replace('"','\"',$rw).'"';
			}
			$rw = '<?php echo sprintf('.$rw.','.$v.');?>';
			$el->write($rw);
		},self::$loadVarsIndex++);
	}
	
	//Core
	function __construct($a=null){
		if(isset($a)){
			if(is_string($a))
				$this->parse($a);
			elseif(is_array($a))
				$this->interpret($a);
		}
	}
	function setParent($tml){
		$this->parent = $tml;
		if($this->parent){
			if(($prev=end($this->parent->childNodes))){
				$prev->nextSibling = &$this;
				$this->previousSibling = &$prev;
			}
			if(!$this->templix&&$this->parent->templix){
				$this->templix = $this->parent->templix;
			}
		}
	}
	function setBuilder($tml){
		$this->constructor = $tml;
		if($this->constructor&&!$this->templix&&$this->constructor->templix){
			$this->templix = $this->constructor->templix;
		}
	}
	function setTemplix($template){
		$this->templix = $template;
	}
	function setNodeName($nodeName){
		$this->nodeName = $nodeName;
	}
	function recursive($callback,$node=null,$break=false){
		if(func_num_args()<2)
			$node = &$this;
		call_user_func_array($callback,[&$node,&$break]);
		if($break)
			return;
		foreach($node->childNodes as $el){
			$this->recursive($callback,$el,$break);
			if($break)
				break;
		}
	}
	function arecursive($callback,$node=null,&$break=false){
		if(func_num_args()<2)
			$node = &$this;
		foreach($node->childNodes as $el){
			$this->arecursive($callback,$el,$break);
			if($break)
				return;
		}
		call_user_func_array($callback,[&$node,&$break]);
	}
	function templix(){
		return $this->templix;
	}
	function getFile($file,$c=null){
		if(!is_file($real=$this->templix->findPath($file)))
			$this->throwException('<'.$c.' "'.$file.'"> template not found ');
		return file_get_contents($real);
	}
	function parseFile($file,$params=null,$c=null){
		if($this->templix)
			return $this->parse($this->getFile($file,$c),$params);
	}
	protected function cacheForge($extra=null,$php=true,$ev=false){
		$code = "$this";
		$h = sha1($code);
		if($php)
			$this->templix->cachePHP($h,'<?php ob_start();?>'.$code.'<?php $this->cacheRegen(__FILE__,ob_get_clean());',true);
		if($ev)
			$this->templix->cacheV($h,$this->evaluate());
		$this->clear();
		$this->head('<?php if($__including=$this->cacheInc(\''.$h.'\''.($extra!==null?(','.(is_string($extra)?"'".str_replace("'","\'",$extra)."'":'unserialize('.serialize($extra).')')):'').'))include $__including;?>');
	}
	protected static function varExport($var,$singlequote=null){
		if(is_array($var)){
			$toImplode = [];
			foreach($var as $key=>$value)
				$toImplode[] = self::varExport($key,$singlequote).'=>'.self::varExport($value,$singlequote);
			$code = '['.implode(',', $toImplode).']';
			return $code;
		}
		$var = var_export($var,true);
		if(!$singlequote&&strpos($var,"'")===0&&strrpos($var,"'")===strlen($var)-1)
			$var = '"'.str_replace('"','\\"',stripslashes(substr($var,1,-1))).'"';
		return $var;
	}
	protected static function exportVars(){
		$args = [];
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
	function triggerLoaded(){
		foreach($this->onLoaded as $callback)
			if(is_callable($callback))
				call_user_func($callback);
		if($this->preventLoad)
			return;
		foreach($this->childNodes as $c)
			$c->triggerLoaded();
		$this->preventLoad = true;
		$this->loaded();
		foreach(array_keys($this->metaAttribution) as $k){
			$key = is_integer($k)?$this->metaAttribution[$k]:$k;
			if(method_exists($this,$m='loaded'.ucfirst(str_replace('-','_',$key)))||(($pos=strpos($key,'-'))!==false&&method_exists($this,$m='loaded'.ucfirst(substr($key,0,$pos).'_'))&&($key=substr($k,$pos+1))))
				$this->$m($this->metaAttribution[$k],$key);
		}
	}
	protected function opened(){
		while((isset($this->metaAttribution['/'])&&$i='/')||(($i=array_search('/',$this->metaAttribution))!==false&&is_integer($i))){
			$this->selfClosed = 2;
			unset($this->metaAttribution[$i]);
		}
		foreach(array_keys($this->metaAttribution) as $k){
			if(self::checkPIOC($this->metaAttribution[$k])){
				$phpNode = new PHP();
				$phpNode->setParent($this);
				$phpNode->setBuilder($this->constructor);
				$phpNode->parse($this->metaAttribution[$k]);
				$this->metaAttribution[$k] = $phpNode;
				if(!is_integer($k))
					$this->attributes[$k] = &$this->metaAttribution[$k];
			}
			elseif(self::checkPIOC($k)){
				$v = $this->metaAttribution[$k];
				unset($this->metaAttribution[$k]);
				$phpNode = new PHP();
				$phpNode->setParent($this);
				$phpNode->setBuilder($this->constructor);
				$phpNode->parse($k.'="'.$v.'"');
				$this->metaAttribution[] = $phpNode;
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
		if(!$this->preventLoad){
			foreach(array_keys($this->metaAttribution) as $k){
				$key = is_integer($k)?$this->metaAttribution[$k]:$k;
				if((method_exists($this,$m='load'.ucfirst(str_replace('-','_',$key)))||(($pos=strpos($key,'-'))!==false&&method_exists($this,$m='load'.ucfirst(substr($key,0,$pos).'_'))&&($key=substr($k,$pos+1)))))
					$this->$m($this->metaAttribution[$k],$key);
			}
			if(!$this->preventLoad){
				$this->load();
			}
			if(method_exists($this,'onExec')){
				$this->head('<?php ob_start();?>');
				$this->foot('<?php echo '.get_class($this).'::triggerExec(ob_get_clean());?>');
			}
		}
		$this->__closed = true;
	}
	
	function __invoke($selector){
		return call_user_func_array([$this,'find'],[$selector,true]);
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
	function offsetExists($k){
		return !!$this->find($k,0);
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
	function _merging($args,$callback){
		foreach($args as $nodes){
			if($nodes instanceof Iterator)
				$nodes = $nodes->getIterator();
			if(!is_array($nodes))
				$nodes = [$nodes];
			foreach($nodes as $node){
				if(is_scalar($node))
					$node = $this->createChild($node);
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
	function searchNode($node, $offset = 0){
        $len = count($this->childNodes);
        for ($i = $offset; $i < $len; $i++) {
            $item = $this->childNodes[$i];
            if ($item===$node)
                return $i;
        }
        return false;
    }
	function match($selector){
		$ABS = new Abstraction($this->nodeName,$this->attributes);
		$c = count($ABS->selector($selector));
		unset($ABS);
		return $c;
	}
		
	function find($selector=null,$index=null){
		$r = [];

		foreach((array)$this->selector($selector) as $el)
			$r[] = $el;

		if($index===true)
			return new Iterator($r);
		elseif($index!==null)
			return isset($r[$index])?$r[$index]:null;
		return $r;
	}
	function children($selector=null,$index=null){
		if($selector==='*'||$selector===null){
			$r = $this->childNodes;
		}
		else{
			$r = [];
			foreach($this->childNodes as $el)
				$r = array_merge($r,(array)$el->selector($selector));
		}
		if($index===true)
			$r = new Iterator($r);
		elseif($index!==null)
			$r = isset($r[$index])?$r[$index]:null;
		return $r;
	}
	function merge(){
		return $this->_merging(func_get_args(),'array_push');
	}
	function premerge(){
		return $this->_merging(func_get_args(),'array_unshift');
	}
	function submerge($node){
		if(is_scalar($node))
			$node = $this->createChild($node);
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
		$a = [];
		foreach($this->metaAttribution as $k=>$v)
			$a[$k] = (string)$v;
		return $a;
	}
	function hasAttribute($k){
		return isset($this->attributes[$k]);
	}
	function getElementsByTagName($nodeName){
		$a = [];
		$this->arecursive(function($el)use(&$a,$nodeName){
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
			$v = $this->createChild($v);
		if($k===null)
			$this->childNodes[] = $v;
		else
			$this->childNodes[$k] = $v;
		return $v;
	}
	function prepend($v){
		if(is_scalar($v))
			$v = $this->createChild($v);
		array_unshift($this->childNodes,$v);
		return $v;
	}
	function each($v){
		return call_user_func($v,$this);
	}
	function replaceWith($obj){
		if(is_scalar($obj))
			$obj = $this->parent->createChild($obj);
		if(!$this->parent){
			$this->clear();
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
	function replace($arg){
		$this->parse($arg);
	}
	function remove(){
		if(!$this->parent){
			$this->clear();
		}
		else{
			foreach($this->parent->childNodes as $i=>$child)
				if($child===$this){
					unset($this->parent->childNodes[$i]);
					break;
				}
		}
		if($this->nextSibling){
			$this->nextSibling->previousSibling = $this->previousSibling;
			unset($this->nextSibling);
		}
		if($this->previousSibling){
			$this->previousSibling->nextSibling = $this->nextSibling;
			unset($this->previousSibling);
		}
	}
	function applyFile($tpl,$params=[]){
		if(($pos=strpos($tpl,':'))!==false)
			$tpl = '../'.substr($tpl,0,$pos).'/'.($base=substr($tpl,$pos+1)).(strpos($base,'/')===false?'/'.$base:'').'.tpl';
		Apply::manualLoad($tpl,$this,$params);
	}
	function before($arg){
		if(is_scalar($arg))
			$arg = $this->parent->createChild($arg);
		array_splice($this->parent->childNodes, $this->getIndex()-1, 0, [$arg]);
		return $arg;
	}
	function after($arg){
		if(is_scalar($arg))
			$arg = $this->parent->createChild($arg);
		array_splice($this->parent->childNodes, $this->getIndex()+1, 0, [$arg]);
		return $arg;
	}
	function getIndex(){
		foreach($this->parent->childNodes as $i=>$child)
			if($child===$this)
				return $i;
	}

	function getInnerMarkups(){
		return implode('',$this->childNodes);
	}
	function getInner(){
		return implode('',$this->innerHead).implode('',$this->childNodes).implode('',$this->innerFoot);
	}
	
	protected function indentationIndex(){
		return ($this->parent?$this->parent->indentationIndex()+($this->nodeName&&!$this->hiddenWrap?1:0):0);
	}
	protected function indentationTab(){
		return "\n".str_repeat("\t",$this->indentationIndex());
	}
	
	function toStringIndented(){
		$str = '';
		$head = implode('',$this->head);
		if(!$this->hiddenWrap){
			if($this->previousSibling){
				if($this->previousSibling->spaceAfterClose){
					$str .= $this->indentationTab();
				}
			}
			elseif($this->parent){
				if($this->parent->spaceAfterOpen){
					$str .= $this->indentationTab();
				}
			}
			$str .= '<'.$this->nodeName;
			foreach($this->metaAttribution as $k=>$v){
				if(is_integer($k)){
					if($this->templix&&$this->templix->isXhtml&&isset($this->attributes[$v])&&$v==$this->attributes[$v])
						$str .= ' '.$v.'="'.$v.'"';
					else
						$str .= ' '.$v;
				}
				else{
					$str .= ' '.$k.'="'.$v.'"';
				}
			}
			if($this->selfClosed&&$this->templix&&$this->templix->isXhtml)
				$str .= '></'.$this->nodeName;
			elseif($this->selfClosed>1)
				$str .= ' /';
			$str .= '>';
			if($this->spaceAfterOpen)
				$str .= ' ';
		}
		$str .= $this->getInner();
		$foot = implode('',$this->foot);
		if(!$this->selfClosed&&!$this->hiddenWrap){
			if(!($lc=end($this->childNodes))||$lc->spaceAfterClose){
				$str .= $this->indentationTab();
			}
			$str .= '</'.$this->nodeName.'>';
		}
		$str = $head.$str.$foot;
		if($this->spaceAfterClose)
			$str .= ' ';
		return $str;
	}
	
	function toString(){
		$str = '';
		$head = implode('',$this->head);
		if(!$this->hiddenWrap){
			$str .= '<'.$this->nodeName;
			foreach($this->metaAttribution as $k=>$v){
				if(is_integer($k)){
					if($this->templix&&$this->templix->isXhtml&&isset($this->attributes[$v])&&$v==$this->attributes[$v])
						$str .= ' '.$v.'="'.$v.'"';
					else
						$str .= ' '.$v;
				}
				else{
					$str .= ' '.$k.'="'.$v.'"';
				}
			}
			if($this->selfClosed&&$this->templix&&$this->templix->isXhtml)
				$str .= '></'.$this->nodeName;
			elseif($this->selfClosed>1)
				$str .= ' /';
			$str .= '>';
			if($this->spaceAfterOpen)
				$str .= ' ';
		}
		$str .= $this->getInner();
		$foot = implode('',$this->foot);
		if(!$this->selfClosed&&!$this->hiddenWrap)
			$str .= '</'.$this->nodeName.'>';
		$str = $head.$str.$foot;
		if($this->spaceAfterClose)
			$str .= ' ';
		return $str;
	}
	function getTemplix(){
		return $this->templix?$this->templix:($this->parent?$this->parent->getTemplix():($this->constructor?$this->constructor->getTemplix():null));
	}
	function __toString(){
		if(($t=$this->getTemplix())&&$t->devTemplate)
			return $this->toStringIndented();
		else
			return $this->toString();
	}
	function clear(){
		$this->clearInner();
		$this->hiddenWrap = true;
		$this->head = [];
		$this->foot = [];
	}
	function clearInner(){
		$this->innerHead = [];
		$this->innerFoot = [];
		$this->childNodes = [];
	}
	function head($arg,$index=null){
		if(!is_null($index)){
			if($index===true){
				array_push($this->head,$arg);
			}
			else{
				array_splice($this->head, $index, 0,[$arg]);
			}
		}
		else{
			array_unshift($this->head,$arg);
		}
		return $arg;
	}
	function foot($arg,$index=null){
		if(!is_null($index)){
			if($index===true){
				array_unshift($this->foot,$arg);
			}
			else{
				array_splice($this->foot, $index, 0,[$arg]);
			}
		}
		else{
			array_push($this->foot,$arg);
		}
		return $arg;
	}
	function innerHead($arg,$index=null){
		if(!is_null($index)){
			if($index===true){
				array_push($this->innerHead,$arg);
			}
			else{
				array_splice($this->innerHead, $index, 0,[$arg]);
			}
		}
		else{
			array_unshift($this->innerHead,$arg);
		}
		return $arg;
	}
	function innerFoot($arg,$index=null){
		if(!is_null($index)){
			if($index===true){
				array_unshift($this->innerFoot,$arg);
			}
			else{
				array_splice($this->innerFoot, $index, 0,[$arg]);
			}
		}
		else{
			array_push($this->innerFoot,$arg);
		}
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
	function tmpAttr($k){
		$a = (array)parse_str($this->attr('tmp-attr'));
		if(func_num_args()<2){
			return isset($a[$k])?$a[$k]:null;
		}
		if(is_array($k)){
			foreach($k as $_k=>$v)
				$a[$_k] = $v;
		}
		else{
			$a[$k] = func_get_arg(1);
		}
		$this->attr('tmp-attr',http_build_query($a));
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
			if(is_integer($from)){
				$afrom = $this->metaAttribution[$to];
				$ato = $to;
			}
			else
				$ato = $afrom = $from;
			if(isset($this->attributes["$afrom"]))
				unset($this->attributes["$afrom"]);
			$this->attributes["$ato"] = &$this->metaAttribution["$to"];
		}
	}

	private $__metaData = [];
	function data($k,$v=null){
		if(func_num_args()>1)
			$this->__metaData[$k] = $v;
		elseif(is_array($k))
			foreach($k as $_k=>$v)
				$this->__metaData[$_k] = $v;
		else
			return isset($this->__metaData[$k])?$this->__metaData[$k]:null;
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
			$arg = $this->parent->createChild($arg);
			if(isset($arg->childNodes[0]))
				$arg = $arg->childNodes[0];
			$arg->selfClosed = null;
		}
		$arg->parent = $this->parent;
		array_splice($this->parent->childNodes, $this->getIndex(), 1, [$arg]);
		$arg->childNodes[] = $this;
		$this->parent = $arg;
	}
	function unwrap($arg='*'){ #experimental - stranges result when used with iterator just wraped before
		$ref = $this->closest($arg);
		$ref->replaceWith($ref->getInner());
	}
	
	function createChild($parse=null,$builder=null){
		$tml = new Markup();
		$tml->setParent($this);
		if(isset($builder))
			$tml->setBuilder($builder);
		else
			$tml->setBuilder($this->constructor);
		if(isset($parse))
			$tml->parse($parse);
		return $tml;
	}
	
	
	function addToCurrent($name,$attributes,$class=null){
		if(!$this->currentTag)
			$this->currentTag = $this;
		if($class===true)
			$class = __NAMESPACE__.'\\'.$name;
		$c = $class?$class:$this->getClass($name);
		$node = new $c();
		$node->setBuilder($this);
		$node->setParent($this->currentTag);
		$node->setNodeName($name);
		$node->make($attributes);
		$node->lineNumber = $this->lineNumber;
		$node->characterNumber = $this->characterNumber;
		$this->currentTag[] = $node;
		return $node;
	}
	private static function prefixClassName($c){
		if(in_array(strtolower($c),['__halt_compiler','abstract','and','array','as','break','callable','case','catch','class','clone','const','continue','declare','default','die','do','echo','else','elseif','empty','enddeclare','endfor','endforeach','endif','endswitch','endwhile','eval','exit','extends','final','for','foreach','function','global','goto','if','implements','include','include_once','instanceof','insteadof','interface','isset','list','namespace','new','or','print','private','protected','public','require','require_once','return','static','switch','throw','trait','try','unset','use','var','while','xor','__class__','__dir__','__file__','__function__','__line__','__method__','__namespace__','__trait__',])){
			$c = '_'.$c;
		}
		return $c;
	}
	function getClass($n){
		$n = str_replace(' ','_',ucwords(preg_replace("/[^A-Za-z0-9 ]/", ' ', $n)));
		if($this->templix)
			$prefixs = $this->templix->getPluginPrefix();
		else
			$prefixs = Templix::getPluginPrefixDefault();
		foreach($prefixs as $ns){
			if(false!==$p=strrpos($n,':')){
				$c = $ns.ucfirst(str_replace(' ', '\\', ucwords(str_replace('.', ' ',substr($n,0,$p))))).'\\'.self::prefixClassName(ucfirst(substr($n,$p+1)));
				if(class_exists($c))
					return $c;
			}
			elseif(class_exists($c=$ns.self::prefixClassName(ucfirst($n))))
				return $c;
		}
		return __NAMESPACE__.'\\Markup';
	}
	function exceptionContext(){
		return ' on "'.$this->templix->getPath().':'.$this->lineNumber.'#'.$this->characterNumber.'"';
	}
	
	
	
	//Parser
	static function initialize(){
		self::$PI_HEX = [self::strToHex(self::$PI_STR[0]),self::strToHex(self::$PI_STR[1])];
		self::$PIO_L = strlen(self::PIO);
		self::$PIC_L = strlen(self::PIC);
	}
	static function phpImplode($tid,$o){
		$a = &$o->__phpSRC;
		$open = null;
		$str = '';
		$id = '';
		for($i=0;$i<strlen($tid);$i++){
			if(substr($tid,$i,self::$PIO_L)==self::PIO){
				$open = true;
				$i+=self::$PIO_L-1;
			}
			elseif(substr($tid,$i,self::$PIC_L)==self::PIC){
				$open = false;
				$i+=self::$PIC_L-1;
				$str .= $a[$id];
				unset($a[$id]);
				$id = '';
			}
			elseif($open)
				$id .= $tid{$i};
			else
				$str .= $tid{$i};
		}
		if(substr($str,-3)=='=""')
			$str = substr($str,0,-3);
		return $str;
	}
	static function short_open_tag(&$s){
		$str = '';
		$c = strlen($s)-1;
		for($i=0;$i<=$c;$i++){
			if($s[$i].@$s[$i+1]=='<?'&&@$s[$i+2]!='='&&(@$s[$i+2].@$s[$i+3].@$s[$i+4])!='php'){
				$y = $i+2;
				$tmp = '<?php ';
				do{
					$p = strpos($s,'?>',$y);
					if($p===false)
						break;
					$p += 2;
					$tmp .= substr($s,$y,$p-$y);
					$tk = @token_get_all(trim($tmp));
					$tk = end($tk);
					$y = $p;
				}
				while(!(is_array($tk)&&$tk[0]===T_CLOSE_TAG));
				$str .= $tmp;
				$i = $y-1;
			}
			else
				$str .= $s[$i];
		}
		$s = $str;
		return $s;
	}
	private function parseML($xmlText){
		self::short_open_tag($xmlText);
		$tokens = token_get_all(str_replace(self::$PI_STR,self::$PI_HEX,$xmlText));
		$xmlText = '';
		$open = 0;
		$php = '';
		$xml = '';
		foreach($tokens as $token){
			if(is_array($token)){
				switch($token[0]){
					case T_OPEN_TAG:
						$open = 1;
						$xmlText .= $xml;
						$xml = '';
						$php = '<?php ';
					break;
					case T_OPEN_TAG_WITH_ECHO:
						$open = 2;
						$xmlText .= $xml;
						$xml = '';
						$php = '<?php echo ';
					break;
					case T_CLOSE_TAG:
						$uid = uniqid(null,true);
						$this->__phpSRC[$uid] = $php.($open===2&&substr(trim($php),-1)!=';'?';':'').'?>';
						$open = 0;
						$xmlText .= self::PIO.$uid.self::PIC;
						$php = '';
					break;
					default:
						if($open)
							$php .= $token[1];
						else
							$xml .= $token[1];
					break;
				}
			}
			else{
				if($open)
					$php .= $token;
				else
					$xml .= $token;
			}
		}
		if($open){
			$uid = uniqid(null,true);
			$this->__phpSRC[$uid] = $php.($open===2?';'&&substr(trim($php),-1)!=';':'').'?>';
			$xmlText .= self::PIO.$uid.self::PIC;
		}
		else
			$xmlText .= $xml;
		
		$xmlText = str_replace(array_keys($this->parseReplacement),array_values($this->parseReplacement),$xmlText);
		
		$state = self::STATE_PARSING;
		$charContainer = '';
		$quoteType = '';
		$total = strlen($xmlText);
		for($i=0;$i<$total;$i++){
			$currentChar = $xmlText{$i};
			$this->characterNumber += 1;
			switch($currentChar){
				case "\n":
					$this->lineNumber += 1;
					$this->characterNumber = 1;
					$charContainer .= $currentChar;
				break;
				case '<':
					switch($state){
						case self::STATE_PARSING_OPENER:
							$state = self::STATE_PARSING_OPENER;
							$this->fireCharacterData($charContainer);
							$charContainer = '';
						break;
						case self::STATE_PARSING:
							if(!isset($xmlText{$i+1}))
								$this->throwException('Unexpected end of file, expected end after '.$currentChar);
							if ($xmlText{$i+1}=='!'){
								$this->fireCharacterData($charContainer);
								if(substr($charContainer,1,7)!='[CDATA['&&substr($xmlText,$i+2,2)!='--'){
									$state = self::STATE_PROLOG_EXCLAMATION;
									$charContainer = '';
								}		
								$charContainer .= $currentChar;
							}
							else{
								$state = self::STATE_PARSING_OPENER;
								$this->fireCharacterData($charContainer);
								$charContainer = '';
							}
						break;
						case self::STATE_ATTR_VALUE:
						case self::STATE_NOPARSING:
						case self::STATE_PARSING_COMMENT:
							$charContainer .= $currentChar;
						break;
						default:
							$this->fireCharacterData($charContainer);
							$charContainer = '';
							if(!isset($xmlText{$i+1}))
								$this->throwException('Unexpected end of file, expected end after '.$currentChar);
							if ($xmlText{$i+1}=='!'){
								$this->fireCharacterData($charContainer);
								$state = self::STATE_PROLOG_EXCLAMATION;
								$charContainer .= $currentChar;
							}
							else {
								$state = self::STATE_PARSING;
								$i+=-1;
							}
						break;
					}
				break;
				case '=':
					switch($state){
						case self::STATE_PARSING_OPENER:
							if(!isset($xmlText{$i+1}))
								$this->throwException('Unexpected end of file, expected end after '.$currentChar);
							$quote = $xmlText{$i+1};
							$y = $i+2;
							$charContainer .= '='.$quote;
							while(($ch=$xmlText{$y++})!=$quote){
								$charContainer .= $ch;
								if(!isset($xmlText{$y+1}))
									$this->throwException('Unexpected end of file, expected end after '.$ch);
							}
							$charContainer .= $quote;
							$i = $y-1;
						break;
						case self::STATE_PARSING:
							if (substr($charContainer, 0, 8) == '![CDATA['){
								$charContainer .= $currentChar;
								break;
							}
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '-':
					switch($state){
						case self::STATE_PARSING_OPENER:
						case self::STATE_PARSING:						
							if (
								isset($xmlText{$i-1})&&
								isset($xmlText{$i-2})&&
								isset($xmlText{$i-3})&&
								$xmlText{$i-1}=='-'&&
								$xmlText{$i-2}=='!'&&
								$xmlText{$i-3}=='<'
							)
							{
								$state = self::STATE_PARSING_COMMENT;
								$charContainer = ' ';
							}
							else
								$charContainer .= $currentChar;							
						break;
						case self::STATE_PROLOG_EXCLAMATION:
							$state = self::STATE_PROLOG_COMMENT;
							$charContainer = '';
						break;
						case self::STATE_PROLOG_COMMENT:
							if (!(
								(isset($xmlText{$i+1}) && isset($xmlText{$i+2}) && $xmlText{$i+1}=='-' && $xmlText{$i+2}=='>') ||
								(isset($xmlText{$i+1}) && $xmlText{$i+1}=='>') ||
								(isset($xmlText{$i-1}) && isset($xmlText{$i-2}) && $xmlText{$i-1}=='-' && $xmlText{$i-2}== '!')
							))
								$charContainer .= $currentChar;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '"':
				case "'":
					switch($state){
						case self::STATE_PARSING_OPENER:
							$state = self::STATE_ATTR_VALUE;
							$quoteType = $currentChar;
						break;
						case self::STATE_ATTR_VALUE:
							if($quoteType==$currentChar)
								$state = self::STATE_PARSING_OPENER;
						break;
					}
					$charContainer .= $currentChar;
				break;
				case '>':
					switch($state){
						case self::STATE_NOPARSING:
							$on = $this->currentTag;
							$nn = $on->nodeName;
							$nnn = '</'.$nn.'>';
							$lnn = strlen($nnn)*-1;
							$charContainer .= $currentChar;
							if(substr($charContainer,$lnn)==$nnn){
								$charContainer = substr($charContainer,0,$lnn);
								$textNode = new TEXT();
								$textNode->setParent($on);
								$textNode->setNodeName('TEXT_UNPARSED');
								$textNode->setBuilder($this);
								$textNode->parse($charContainer);
								$on[] = $textNode;
								$this->fireEndElement($nn);
								$charContainer = '';
								$state = self::STATE_PARSING;
							}
						break;
						case self::STATE_PARSING_OPENER:						
						case self::STATE_PARSING:						
							if ((substr($charContainer, 0, 8) == '![CDATA[') &&
								!((self::getCharFromEnd($charContainer, 0) == ']') &&
								(self::getCharFromEnd($charContainer, 1) == ']'))) {
								$charContainer .= $currentChar;
							}
							else {
								$state = self::STATE_PARSING;
								$firstChar = isset($charContainer{0})?$charContainer{0}:'';
								$myAttributes = [];
								switch($firstChar){
									case '/':
										$tagName = substr($charContainer, 1);
										$this->fireEndElement($tagName);
									break;
									case '!':
										$upperCaseTagText = strtoupper($charContainer);
										if (strpos($upperCaseTagText, '![CDATA[') !== false) {
											$openBraceCount = 0;
											$textNodeText = '';
											for($y=0;$y<strlen($charContainer);$y++) {
												$currentChar = $charContainer{$y};
												if (($currentChar == ']') && ($charContainer{($y + 1)} == ']'))
													break;
												else if ($openBraceCount > 1)
													$textNodeText .= $currentChar;
												else if ($currentChar == '[')
													$openBraceCount++;
											}
											$this->fireCDataSection($textNodeText);
										}
									break;
									default:
										if ((strpos($charContainer, '"') !== false) || (strpos($charContainer, "'") !== false)){
											$tagName = '';
											for($y=0;$y<strlen($charContainer);$y++){
												$currentChar = $charContainer{$y};
												if (($currentChar == ' ') || ($currentChar == "\t") ||
													($currentChar == "\n") || ($currentChar == "\r") ||
													($currentChar == "\x0B")) {
													$myAttributes = self::parseAttributes(substr($charContainer, $y));
													break;
												}
												else
													$tagName .= $currentChar;
											}
											if (strrpos($charContainer, '/')==(strlen($charContainer)-1)){
												$this->fireElement($tagName, $myAttributes);
											}
											else
												$this->fireStartElement($tagName, $myAttributes, $state);
										}
										else{
											if(strpos($charContainer,' ')!==false){
												$x = explode(' ',$charContainer);
												$charContainer = array_shift($x);
												foreach($x as $k)
													if($k=='/')
														$charContainer .= '/';
													else
														$myAttributes[] = $k;
											}
											if (strpos($charContainer, '/') !== false) {
												$charContainer = substr($charContainer, 0, (strrchr($charContainer, '/') - 1));
												$this->fireElement($charContainer, $myAttributes);
											}
											else {
												$this->fireStartElement($charContainer, $myAttributes, $state);
											}
										}
									break;					
								}
								$charContainer = '';
							}
						break;
						case self::STATE_PROLOG_COMMENT:
							$state = self::STATE_PARSING;
							$this->fireComment($charContainer);
							$charContainer = '';
						break;
						case self::STATE_PROLOG_DTD:
							$state = self::STATE_PARSING;
							$this->fireDTD($charContainer.$currentChar);
							$charContainer = '';
						break;
						case self::STATE_PROLOG_INLINEDTD:
							if(isset($xmlText{$i-1}) && $xmlText{$i-1}==']'){
								$state = self::STATE_PARSING;
								$this->fireDTD($charContainer.$currentChar);						
								$charContainer = '';
							}
							else
								$charContainer .= $currentChar;
						break;
						case self::STATE_PARSING_COMMENT:
							if(
								isset($xmlText{$i-1})&&isset($xmlText{$i-2})&&
								$xmlText{$i-1}=='-'&&$xmlText{$i-2}=='-'
							){
								$this->fireComment(substr($charContainer,0,strlen($charContainer)-2));
								$charContainer = '';
								$state = self::STATE_PARSING;
							}
							else
								$charContainer .= $currentChar;
						break;
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case 'D':
					switch($state){
						case self::STATE_PROLOG_EXCLAMATION:
							$state = self::STATE_PROLOG_DTD;	
							$charContainer .= $currentChar;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				case '[':
					switch($state){
						case self::STATE_PROLOG_DTD:
							$charContainer .= $currentChar;
							$state = self::STATE_PROLOG_INLINEDTD;
						break;
						case self::STATE_NOPARSING:
						default:
							$charContainer .= $currentChar;
						break;
					}
				break;
				default:
					$charContainer .= $currentChar;
				break;
			}
		}
		$this->fireCharacterData($charContainer);
		switch($state){
			case self::STATE_NOPARSING:
				$this->throwException('Unexpected end of file, expected end of noParse Tag </'.$this->currentTag->nodeName.'>');
			break;
			case self::STATE_PARSING_COMMENT:
				$this->throwException('Unexpected end of file, expected end of comment Tag -->');
			break;
			case self::STATE_PARSING:
			default:
				if($this->currentTag&&$this->currentTag->nodeName&&!$this->currentTag->__closed&&!$this->currentTag->selfClosed)
					$this->throwException('Unexpected end of file, expected end of Tag </'.$this->currentTag->nodeName.'>');
			break;
		}
	}
	private static function getCharFromEnd($text, $index) {
		$len = strlen($text);
		$char = $text{($len - 1 - $index)};
		return $char;
	}
	private static function parseAttributes($attrText){
		$attrArray = [];
		$total = strlen($attrText);
		$keyDump = '';
		$valueDump = '';
		$currentState = self::STATE_ATTR_NONE;
		$quoteType = '';
		$keyDumpI = 0;
		for($i=0;$i<$total;$i++){	
			$currentChar = $attrText{$i};
			if($currentState==self::STATE_ATTR_NONE&&trim($currentChar))
				$currentState = self::STATE_ATTR_KEY;
			switch ($currentChar){
				case '=':
					if ($currentState == self::STATE_ATTR_VALUE)
						$valueDump .= $currentChar;
					else {
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = '';
					}
				break;
				case '"':
					if ($currentState == self::STATE_ATTR_VALUE) {
						if ($quoteType=='')
							$quoteType = '"';
						elseif ($quoteType == $currentChar) {
							$keyDump = trim($keyDump);
							$attrArray[$keyDump] = trim($valueDump)?$valueDump:'';
							$keyDump = $valueDump = $quoteType = '';
							$currentState = self::STATE_ATTR_NONE;
						}
						else
							$valueDump .= $currentChar;
					}
					else{
						$keyDump = $keyDumpI++;
						$valueDump = '';
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = '"';
					}
				break;
				case "'":
					if ($currentState == self::STATE_ATTR_VALUE) {
						if ($quoteType == '')
							$quoteType = "'";
						elseif ($quoteType == $currentChar){
							$keyDump = trim($keyDump);
							$attrArray[$keyDump] = trim($valueDump)?$valueDump:'';
							$keyDump = $valueDump = $quoteType = '';
							$currentState = self::STATE_ATTR_NONE;
						}
						else
							$valueDump .= $currentChar;
					}
					else{
						$keyDump = $keyDumpI++;
						$valueDump = '';
						$currentState = self::STATE_ATTR_VALUE;
						$quoteType = "'";
					}
				break;
				case "\t":
				case "\x0B":
				case "\n":
				case "\r":
				case ' ':
					if($currentState==self::STATE_ATTR_KEY){
						$currentState = self::STATE_ATTR_NONE;
						if($keyDump)
							$attrArray[] = trim($keyDump);
						$keyDump = $valueDump = $quoteType = '';
					}
					elseif($currentState==self::STATE_ATTR_VALUE)
						$valueDump .= $currentChar;
				break;
				default:
					if ($currentState == self::STATE_ATTR_KEY)
						$keyDump .= $currentChar;
					else
						$valueDump .= $currentChar;
				break;
			}
		}
		if(trim($keyDump))
			$attrArray[] = trim($keyDump);
		return $attrArray;
	}
	private static function strToHex($s){
		$h = '';
		for ($i=0;$i<strlen($s);$i++)
			$h .= '&#'.ord($s{$i}).';';
		return $h;
	}
	protected static function checkPIOC($check){
		return strpos($check,self::PIO)!==false&&strpos($check,self::PIC)!==false;
	}
	
	private function fireElement($name,$attributes){
		$attributes['/'] = '';
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireElement($n,$attributes);
		}
		$this->addToCurrent($name,$attributes)->closed();
	}
	private function fireStartElement($name,$attributes,&$state=null){
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireStartElement($n,$attributes);
		}
		$this->currentTag = $this->addToCurrent($name,$attributes);
		if($this->currentTag->noParseContent)
			$state = self::STATE_NOPARSING;
		if($this->currentTag->selfClosed===true){
			$this->currentTag->closed();
			if($this->currentTag->parent)
				$this->currentTag = $this->currentTag->parent;
		}
	}
	private function fireEndElement($name){
		if(($pos=strpos($name,'&'))!==false){
			$x = explode('&',$name);
			$x = array_reverse($x);
			$name = array_pop($x);
			foreach($x as $n)
				$this->fireEndElement($n);
		}
		if($name!=$this->currentTag->nodeName)
			$this->throwException('Unexpected </'.$name.'>, expected </'.$this->currentTag->nodeName.'>');
		$this->currentTag->closed();
		if($this->currentTag->parent)
			$this->currentTag = $this->currentTag->parent;
	}
	private function fireDTD($doctype){
		$this->addToCurrent('DOCTYPE',$doctype,true);
	}
	private function fireComment($comment){
		$this->addToCurrent('COMMENT',$comment,true);
	}
	private function fireCharacterData($text){
		if($text){
			if(trim($text)){
				$node = $this->addToCurrent('TEXT',$text,true);
				$f = substr($text,0,1);
				$l = substr($text,-1);
				if($l==" "||$l=="\t"||$l=="\0"||$l=="\x0B"||$l=="\n"){
					$node->spaceAfterClose = true;
				}
				if($f==" "||$f=="\t"||$f=="\0"||$f=="\x0B"||$f=="\n"){
					$this->currentTag->spaceAfterOpen = true;
				}
			}
			elseif($this->currentTag){
				if($node=end($this->currentTag->childNodes)){
					$node->spaceAfterClose = true;
				}
				else{
					$this->currentTag->spaceAfterOpen = true;
				}
			}
		}
	}
	private function fireCDataSection($text){
		$this->addToCurrent('CDATA',$text,true);
	}
	function evalue($v,$vars=null){
		if(isset($vars))
			extract($vars);
		ob_start();
		eval('?>'.$v);
		return ob_get_clean();
	}
	function evaluate(){
		return ob_start()&&eval('?>'.$this)!==false?ob_get_clean():'';
	}
	function parse($arg){
		$this->clear();
		if(!is_string($arg))
			$arg = "$arg";
		$n = func_num_args();
		if($n>1&&($params=func_get_arg(1)))
			foreach((array)$params as $k=>$v)
				$arg = str_replace('{{:'.$k.':}}',$v,$arg);
		$pos = 0;
		if(preg_match_all('/\\{\\{::(.*?)::\\}\\}/', $arg, $matches))
			foreach($matches[1] as $i=>$eve)
				$arg = substr($arg,0,$pos=strpos($arg,$matches[0][$i],$pos)).$this->evalue($eve).substr($arg,$pos+strlen($matches[0][$i]));
		$this->parseML($arg);
		if($n<3||!func_get_arg(2))
			$this->triggerLoaded();
	}
	function make($arg){
		if(is_string($arg)){
			$this->parse($arg);
		}
		else{
			$this->interpret($arg);
		}
	}
	protected function interpret($attributes,$nodeName=null){
		if(isset($nodeName))
			$this->nodeName = $nodeName;
		$this->metaAttribution = $attributes;
		$this->opened();
	}
	protected function throwException($msg){
		if($this->templix)
			$msg .= $this->exceptionContext();
		throw new MarkupException($msg);
	}
	function isHiddenWrap(){
		return $this->hiddenWrap;
	}
	function __clone(){
		foreach($this->childNodes as $i=>$node){
			$this->childNodes[$i] = clone $node;
		}
		foreach($this->head as $i=>$node){
			if($node instanceof Markup){
				$this->head[$i] = clone $node;
			}
		}
		foreach($this->innerHead as $i=>$node){
			if($node instanceof Markup){
				$this->innerHead[$i] = clone $node;
			}
		}
		foreach($this->innerFoot as $i=>$node){
			if($node instanceof Markup){
				$this->innerFoot[$i] = clone $node;
			}
		}
		foreach($this->foot as $i=>$node){
			if($node instanceof Markup){
				$this->foot[$i] = clone $node;
			}
		}
	}
}
Markup::initialize();