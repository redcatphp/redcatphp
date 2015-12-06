<?php
namespace RedCat\Plugin\PHPConfig;
use Pharborist\Parser;
use Pharborist\Namespaces\NamespaceNode;
use Pharborist\Filter;

use Pharborist\Types\ArrayNode;
use Pharborist\Types\ArrayPairNode;
use Pharborist\Types\StringNode;

class TokenTree implements \ArrayAccess{
	private $data = [];
	private $tokens;
	function __construct($filename){
		$tree = Parser::parseFile($filename);
		$collectArrayR = function(ArrayNode $node,&$a=null)use(&$collectArrayR){
			$keys = [];
			$vals = [];
			$i = 0;
			foreach($node->getElements() as $el){
				if($el instanceof ArrayPairNode){
					$k = (string)$el->getKey();
					$k = trim($k,'"\'');
					$keys[] = $k;
					$v = $el->getValue();
					if($v instanceof ArrayNode){
						$v = $collectArrayR($v);
					}
					else{
						$v = (string)$v;
					}
					$vals[] = $v;
				}
				elseif($el instanceof ArrayNode){
					$vals[] = $collectArrayR($el);
					$keys[] = $i;
					$i++;
				}
				else{
					$vals[] = (string)$el;
					$keys[] = $i;
					$i++;
				}
			}
			$a = array_combine($keys,$vals);
			return $a;
			
		};
		$found = false;
		$tree->walk(function($node)use(&$found,&$collectArrayR,&$a){
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
			return $var;
		}
	}
	function __toString(){
		$str = self::var_codify($this->data);;
		return $str;
	}
}