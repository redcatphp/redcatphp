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
	function __construct($filename){
		$this->tree = Parser::parseFile($filename);
		$collectArrayR = function(ArrayNode $node,&$a=null)use(&$collectArrayR){
			$keys = [];
			$vals = [];
			$i = 0;
			foreach($node->getElements() as $el){
				if($el instanceof ArrayPairNode){
					$k = (string)$el->getKey();
					$k = trim($k,'"\'');
					$v = $el->getValue();
					if($v instanceof ArrayNode){
						$v = $collectArrayR($v);
					}
					else{
						$v = (string)$v;
					}
					$keys[] = $k;
					$vals[] = $v;
				}
				elseif($el instanceof ArrayNode){
					$keys[] = $i;
					$vals[] = $collectArrayR($el);
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
			
		};
		$found = false;
		$this->tree->walk(function($node)use(&$found,&$collectArrayR,&$a){
			if($found)
				return;
			if($node instanceof ArrayNode){
				$collectArrayR($node,$a);
				$found = true;
			}
		});
		$this->data = $a;
	}
	function offsetSet($k,$v){
		$this->data[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function &offsetGet($k){
		return $this->data[$k];
	}
	function offsetUnset($k){
		unset($this->data[$k]);
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
	
	function update(){
		$collectRefR = function(ArrayNode $node,$data)use(&$collectRefR){
			$i = 0;
			foreach($node->getElements() as $el){
				if($el instanceof ArrayPairNode){
					$k = (string)$el->getKey();
					$k = trim($k,'"\'');
					$v = $el->getValue();
					if(!isset($data[$k])){
						$next = $el->next();
						if($next&&$next->getType()===',')
							$next->remove();
						while(($next=$el->next()) instanceof WhitespaceNode){
							$next->remove();
						}
						$el->remove();
					}
					else{
						if($v instanceof ArrayNode){
							$v = $collectRefR($v,$data[$k]);
							unset($data[$k]);
						}
						else{
							$v->replaceWith(Parser::parseExpression($data[$k]));
							unset($data[$k]);
						}
					}
				}
				elseif($el instanceof ArrayNode){
					if(!isset($data[$i])){
						$el->remove();
					}
					else{
						$collectRefR($el,$data[$i]);
						unset($data[$i]);
						$i++;
					}
				}
				else{
					if(!isset($data[$i])){
						$el->remove();
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
				$prev = $el->previous();
				$comma = false;
				$list = $node->getElementList();
				$children = [];
				foreach($list->children() as $child){
					$children[] = $child;
				}
				$prev = end($children);
				do{
					if((string)$prev===','){
						$comma = true;
						break;
					}
				}
				while(($prev=$prev->previous()) instanceof WhitespaceNode);
				
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
		};
		
		$found = false;
		$this->tree->walk(function($node)use(&$found,&$collectRefR){
			if($found) return;
			if($node instanceof ArrayNode){
				$collectRefR($node,$this->data);
				$found = true;
			}
		});
		
	}
	
	function dot($dotKey,$value=null){
		$dotKey = explode('.',$dotKey);
		$k = array_shift($dotKey);
		$set = func_num_args()>1;
		if(!isset($this->data[$k])&&!$set)
			return;
		$v = &$this->data[$k];
		while($k = array_shift($dotKey)){
			if(!isset($v[$k])&&!$set)
				return;
			$v = &$v[$k];
		}
		if($set)
			$v = $value;
		//dd($this->data);
		return $v;
	}
	
	function __toString(){
		$this->update();
		$str = (string)$this->tree;
		return $str;
	}
}