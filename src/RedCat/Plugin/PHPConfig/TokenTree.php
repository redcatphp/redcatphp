<?php
namespace RedCat\Plugin\PHPConfig;
use Pharborist\Parser;
use Pharborist\Namespaces\NamespaceNode;
use Pharborist\Filter;

use Pharborist\Node;
use Pharborist\Token;
use Pharborist\WhitespaceNode;
use Pharborist\Types\ArrayNode;
use Pharborist\Types\ArrayPairNode;

class TokenTree implements \ArrayAccess{
	private $data = [];
	private $tree;
	private $once;
	function __construct($filename){
		$this->tree = Parser::parseFile($filename);
		$this->once = false;
		$this->tree->walk([$this,'onceArray']);
	}
	function onceArray(Node $node){
		if($this->once)
			return;
		if($node instanceof ArrayNode){
			$this->once = true;
			$this->collectArray($node,$this->data);
		}
	}
	private function collectArray(ArrayNode $node,&$a=null){
		$keys = [];
		$vals = [];
		$i = 0;
		foreach($node->getElements() as $el){
			if($el instanceof ArrayPairNode){
				$k = (string)$el->getKey();
				$k = trim($k,'"\'');
				$v = $el->getValue();
				if($v instanceof ArrayNode){
					$v = $this->collectArray($v);
				}
				else{
					$v = (string)$v;
				}
				$keys[] = $k;
				$vals[] = $v;
			}
			elseif($el instanceof ArrayNode){
				$keys[] = $i;
				$vals[] = $this->collectArray($el);
				$i++;
			}
			else{
				$keys[] = $i;
				$vals[] = (string)$el;
				$i++;
			}
		}
		$a = array_combine($keys,$vals);
		return $a;
	}
	private static function var_export($var, $indent=0){
		switch(gettype($var)){
			case 'string':
				return "'".addcslashes($var, '\'')."'";
			case 'array':
				$indexed = array_keys($var) === range(0, count($var) - 1);
				$r = [];
				foreach($var as $key => $value){
					$r[] = str_repeat("\t",$indent+1)
						 .($indexed?'':self::var_export($key).' => ')
						 .self::var_export($value, $indent+1);
				}
				return "[\n" . implode(",\n", $r) . "\n" . str_repeat("\t",$indent) . "]";
			case 'boolean':
				return $var?'true':'false';
			default:
				if(is_float($var))
					return (string)$var;
				return var_export($var, true);
		}
	}
	private function cleanAround($el){
		$prev = $el;
		while(($prev=$prev->previous()) instanceof WhitespaceNode){
			$prev->remove();
		}
		$next = $el;
		while(($next=$el->next()) instanceof WhitespaceNode){
			$next->remove();
		}
		if($next&&$next->getType()===',')
			$next->remove();
		$el->remove();
	}
	private function updateArray(ArrayNode $node,$data){
		$i = 0;
		foreach($node->getElements() as $el){
			if($el instanceof ArrayPairNode){
				$k = (string)$el->getKey();
				$k = trim($k,'"\'');
				$v = $el->getValue();
				if(!isset($data[$k])){
					$this->cleanAround($el);
				}
				else{
					if($v instanceof ArrayNode&&is_array($data[$k])){
						$v = $this->updateArray($v,$data[$k]);
						unset($data[$k]);
					}
					else{
						$v->replaceWith(Parser::parseExpression($data[$k]));
						unset($data[$k]);
					}
				}
			}
			elseif($el instanceof ArrayNode&&(!isset($data[$i])||is_array($data[$i]))){
				if(!isset($data[$i])){
					$this->cleanAround($el);
				}
				else{
					$this->updateArray($el,$data[$i]);
					unset($data[$i]);
					$i++;
				}
			}
			else{
				if(!isset($data[$i])){
					$this->cleanAround($el);
				}
				else{
					$el->replaceWith(Parser::parseExpression($data[$i]));
					unset($data[$i]);
					$i++;
				}
			}
		}
		
		
		foreach($data as $key=>$val){
			$v = Parser::parseExpression(self::var_codify($val));
			if(!is_integer($key)){
				$v = ArrayPairNode::create(Node::fromValue($key),$v);
			}
			$comma = false;
			$list = $node->getElementList();
			$children = [];
			foreach($list->children() as $child){
				$children[] = $child;
			}
			$prev = end($children);
			if($prev){
				do{
					if((string)$prev===','){
						$comma = true;
						break;
					}
				}
				while(is_object($prev)&&($prev=$prev->previous()) instanceof WhitespaceNode);
			}
			else{
				$comma = true;
			}
			
			$indent = 0;
			$prev = end($children);
			while($prev&&strpos($prev,"\n")===false){
				$prev = $prev->previous();
			}
			$indent = '';
			if($prev){
				$prev = explode("\n",(string)$prev);
				$prev = array_pop($prev);
				for($i=0; $i<strlen($prev); $i++){
					if(in_array($prev[$i],["\t",' ']))
						$indent .= $prev[$i];
					else
						break;
				}
			}
			if(!$comma){
				$list->append(Token::comma());
			}
			$list->append(Token::newline());
			if($indent)
				$list->append(WhitespaceNode::create($indent));
			$list->append($v);
		}
	}
	function onceUpdate($node){
		if($this->once) return;
		if($node instanceof ArrayNode){
			$this->once = true;
			$this->updateArray($node,$this->data);
		}
	}
	function update(){
		$this->once = false;
		$this->tree->walk([$this,'onceUpdate']);
	}
	
	function __toString(){
		$this->update();
		$str = (string)$this->tree;
		return $str;
	}
	
	function offsetExists($k){
		$dotKey = explode('.',$dotKey);
		$k = array_shift($dotKey);
		if(!isset($this->data[$k]))
			return false;
		$v = &$this->data[$k];
		while(null!==$k=array_shift($dotKey)){
			if(!isset($v[$k]))
				return false;
			$v = &$v[$k];
		}
		return true;
	}
	function &offsetGet($key){
		$dotKey = explode('.',$key);
		$k = array_shift($dotKey);
		$v = &$this->data[$k];
		while(null!==$k=array_shift($dotKey)){
			$v = &$v[$k];
		}
		return $v;
	}
	function offsetSet($key,$value){
		$dotKey = explode('.',$key);
		$k = array_shift($dotKey);
		$v = &$this->data[$k];
		while(null!==$k=array_shift($dotKey)){
			$v = &$v[$k];
		}
		$v = $value;
	}
	function offsetUnset($key){
		$dotKey = explode('.',$key);
		$k = array_shift($dotKey);
		if(!isset($this->data[$k]))
			return;
		$v = &$this->data[$k];
		while(null!==$k=array_shift($dotKey)){
			if(!isset($v[$k]))
				return;
			if(empty($dotKey)){
				unset($v[$k]);
			}
			else{
				$v = &$v[$k];
			}
		}
	}
	
	static function var_codify($var, $indent=0){
		if(is_array($var)){
			$indexed = array_keys($var) === range(0, count($var) - 1);
			$r = [];
			foreach($var as $key => $value){
				$r[] = str_repeat("\t",$indent+1)
					 .($indexed?'':"'".addcslashes($key, '\'')."'".' => ')
					 .self::var_codify($value, $indent+1);
			}
			return "[\n" . implode(",\n", $r) . "\n" . str_repeat("\t",$indent) . "]";
		}
		else{
			return (string)$var;
		}
	}
}