<?php namespace Surikat\Templator;
use Surikat\Templator\TML;
use Surikat\Templator\TML_Apply;
use Surikat\Templator\CssSelector;
use Surikat\DependencyInjection\MutatorCall;
class CORE extends PARSER implements \ArrayAccess,\IteratorAggregate{
	use MutatorCall;
	
	var $nodeName;
	var $parent;
	var $constructor;
	var $childNodes = [];
	var $attributes = [];
	var $metaAttribution = [];
	var $previousSibling;
	var $nextSibling;
	var $namespace;
	var $namespaceClass;
	var $_namespaces;
	var $Template;
	
	protected $hiddenWrap;
	protected $preventLoad;
	protected $selfClosed;
	protected $__closed;
	protected $noParseContent;
	protected $footIndentationForce;
	
	protected $foot = [];
	protected $head = [];
	protected $innerFoot = [];
	protected $innerHead = [];
	
	private $selectorService;
	
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
			if(!$this->Template&&$this->parent->Template){
				$this->Template = $this->parent->Template;
			}
		}
	}
	function setBuilder($tml){
		$this->constructor = $tml;
		if($this->constructor&&!$this->Template&&$this->constructor->Template){
			$this->Template = $this->constructor->Template;
		}
	}
	function setTemplate($template){
		$this->Template = $template;
	}
	function setNodeName($nodeName){
		$this->nodeName = $nodeName;
	}
	function recursiveMethod($callback,$node=null,$args=null){
		if(func_num_args()<2)
			$node = &$this;
		call_user_func_array([&$node,$callback],(array)$args);
		foreach($node->childNodes as $el)
			$this->recursiveMethod($callback,$el,$args);
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
	function Template(){
		return $this->Template;
	}
	function getFile($file,$c=null){
		if(!is_file($real=$this->Template->find($file)))
			$this->throwException('&lt;'.$c.' "'.$file.'"&gt; template not found ');
		return file_get_contents($real);
	}
	function parseFile($file,$params=null,$c=null){
		if($this->Template)
			return $this->parse($this->getFile($file,$c),$params);
	}
	protected function cacheForge($extra=null,$php=true,$ev=false){
		$code = "$this";
		$h = sha1($code);
		if($php)
			$this->Template->cachePHP($h,'<?php ob_start();?>'.$code.'<?php $this->cacheRegen(__FILE__,ob_get_clean());',true);
		if($ev)
			$this->Template->cacheV($h,$this->evaluate());
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
		$ABS = new ABSTRACTION($this->nodeName,$this->attributes);
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
	function getAttribute($k){
		return $this->attr($k);
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
			$obj = $this->parent->createChild($obj);
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
	function remove(){
		if(!$this->parent)
			$this->clean();
		else
			foreach($this->parent->childNodes as $i=>$child)
				if($child===$this){
					unset($this->parent->childNodes[$i]);
					break;
				}
	}
	function applyFile($tpl,$params=[]){
		if(($pos=strpos($tpl,':'))!==false)
			$tpl = '../'.substr($tpl,0,$pos).'/'.($base=substr($tpl,$pos+1)).(strpos($base,'/')===false?'/'.$base:'').'.tpl';
		TML_Apply::manualLoad($tpl,$this,$params);
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

	protected function indentationIndex(){
		return ($this->parent?$this->parent->indentationIndex()+($this->nodeName&&!$this->hiddenWrap?1:0):0);
	}
	protected function isIndented(){
		return $this->Dev_Level()->VIEW&&$this->nodeName&&!$this->hiddenWrap;
	}
	protected function indentationTab($force=null){
		if($this->isIndented()||$force)
			return "\n".str_repeat("\t",$this->indentationIndex());
	}
	function getInnerTml(){
		return implode('',$this->childNodes);
	}
	function getInner(){
		return implode('',$this->innerHead).implode('',$this->childNodes).implode('',$this->innerFoot);
	}
	private $maxCharByLine = 80;
	function __toString(){
		$str = $this->indentationTab();
		$head = implode('',$this->head);
		if(!$this->hiddenWrap){
			$str .= '<'.$this->nodeName;
			$maxChar = $this->maxCharByLine;
			$lp = false;
			foreach($this->metaAttribution as $k=>$v){
				if(strlen($str)>$maxChar){
					$maxChar += $this->maxCharByLine;
					if($lp)
						$str .= "\n";
					$str .= $this->indentationTab();
				}
				if(is_integer($k)){
					if($this->Template&&$this->Template->isXhtml&&isset($this->attributes[$v])&&$v==$this->attributes[$v])
						$str .= ' '.$v.'="'.$v.'"';
					else
						$str .= ' '.$v;
				}
				else{
					$str .= ' '.$k.'="'.$v.'"';
				}
				$lp = is_integer($k)&&($v instanceof PHP);
			}
			if($this->selfClosed&&$this->Template&&$this->Template->isXhtml)
				$str .= '></'.$this->nodeName;
			elseif($this->selfClosed>1)
				$str .= ' /';
			$str .= '>';
		}
		$inner = $this->getInner();
		if(substr_count($inner,"\n")<2&&strlen($str)<$this->maxCharByLine
			&&strlen($inner)<$this->maxCharByLine
			&&!empty($this->childNodes)&&$this->isIndented()
		){
			$ind = strlen(array_values($this->childNodes)[0]->indentationTab());
			$inner = substr($inner,$ind);
		}
		$str .= $inner;
		$foot = implode('',$this->foot);		
		if($this->footIndentationForce||(!$this->selfClosed&&!$this->hiddenWrap
			&&(substr_count($str,"\n")+substr_count($foot,"\n"))>1))
			$str .= $this->indentationTab($this->footIndentationForce);
		if(!$this->selfClosed&&!$this->hiddenWrap)
			$str .= '</'.$this->nodeName.'>';
		$str = $head.$str.$foot;
		return $str;
	}
	function clear(){
		$this->clean();
		$this->clearInner();
	}
	function clearInner(){
		$this->innerHead = [];
		$this->innerFoot = [];
		$this->childNodes = [];
	}
	function clean(){
		$this->clearInner();
		$this->hiddenWrap = true;
		$this->head = [];
		$this->foot = [];
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
		$this->childNodes = [];
		$this->attributes = [];
		$this->hiddenWrap = true;
		$this->innerHead = [];
		$this->innerFoot = [];
		$this->head = [];
		$this->foot = [];
	}
	function &head($arg){
		if(func_num_args()>1)
			array_splice($this->head, func_get_arg(1), 0,[$arg]);
		else
			array_unshift($this->head,$arg);
		return $arg;
	}
	function &foot($arg){
		if(func_num_args()>1)
			array_splice($this->foot, func_get_arg(1), 0,[$arg]);
		else
			array_push($this->foot,$arg);
		return $arg;
	}
	function &innerHead($arg){
		if(func_num_args()>1)
			array_splice($this->innerHead, func_get_arg(1), 0,[$arg]);
		else
			array_unshift($this->innerHead,$arg);
		return $arg;
	}
	function &innerFoot($arg){
		if(func_num_args()>1)
			array_splice($this->innerFoot, func_get_arg(1), 0,[$arg]);
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
	function replace($mix){
		$this->parse($mix);
	}

	function addJsScript($js=null){
		if(!$js)
			$js = $this;
		$dom = $this->closest()->find('body',0);
		if(!$dom)
			return;
		$src = trim($js->src);
		if($src){
			$script = $dom->find('script:not([src]):last',0);
			if(!$script){
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
		$dom = $this->closest()->children('head',0);
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
		if($href&&!($script=$dom->children('link[href="'.$href.'"]'.$media_s,0)))
			$dom[] = '<link href="'.$href.'" rel="stylesheet" type="text/css"'.$media.'>';
	}

	function presentProperty(){
		if(strpos(func_get_arg(0),'<?')!==false){
			extract((array)$this->Template->present);
			return $this->evalue(func_get_arg(0));
		}
		return func_get_arg(0);
	}
	function createChild($parse=null,$builder=null){
		$tml = new TML();
		$tml->setParent($this);
		if(isset($builder))
			$tml->setBuilder($builder);
		else
			$tml->setBuilder($this->constructor);
		if(isset($parse))
			$tml->parse($parse);
		return $tml;
	}
}