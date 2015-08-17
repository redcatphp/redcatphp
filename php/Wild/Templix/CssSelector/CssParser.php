<?php
namespace Wild\Templix\CssSelector;
use ArrayObject;
use Wild\Templix\CssSelector\Combinator\Factory;
use Wild\Templix\CssSelector\Filter\Attr;
use Wild\Templix\CssSelector\Filter\_Class;
use Wild\Templix\CssSelector\Filter\Id;
use Wild\Templix\CssSelector\Filter\Pseudo;
use Wild\Templix\CssSelector\Filter\PseudoFactory;
use Wild\Templix\CssSelector\Model\Element;
use Wild\Templix\CssSelector\Model\Factor;
use Wild\Templix\CssSelector\Model\Selector;
use Wild\Templix\CssSelector\TextParserException;
use Wild\Templix\CssSelector\TextParser;
/**
 * selectorList			  = selector {"," selector}
 * selector				  = factor {factor}
 * factor					= combinator element | element
 * element				   = ("*" | identifier) {filter}
 * filter					= class-filter | id-filter | attr-filter | pseudo-filter
 * class-filter			  = "." identifier
 * id-filter				 = "#" identifier
 * attr-filter			   = "[" identifier [attr-operator value] "]"
 * pseudo-filter			 = ":" ( pseudo-first-child-filter
 *							 | pseudo-nth-child-filter )
 * pseudo-nth-child-filter   = "nth-child" "(" number ")"
 * pseudo-first-child-filter = "first-child"
 * identifier				= ( "_" | alphanum ) { "_" | "-" | alphanum }
 * attr-operator			 = "=" | "~="
 * combinator				= ">" | "+" | "~"
 * value					 = quoted-string | alphanum {alphanum}
 */
class CssParser extends TextParser{
	const IDENTIFIER = "[_a-z0-9][_\-a-z0-9]*";
	private $_node;
	private $_pseudoFilters;
	private $_combinators;
	function __construct($target, $charset = "", $mimetype = ""){
		$this->_pseudoFilters = [];
		$this->_combinators = [];		
		$this->_node = $target;
		$this->registerPseudoFilter("first", "PseudoFirst");
		$this->registerPseudoFilter("last", "PseudoLast");
		$this->registerPseudoFilter("eq", "PseudoEq");
		$this->registerPseudoFilter("nth", "PseudoEq");
		$this->registerPseudoFilter("even", "PseudoEven");
		$this->registerPseudoFilter("odd", "PseudoOdd");
		$this->registerPseudoFilter("lt", "PseudoLt");
		$this->registerPseudoFilter("gt", "PseudoGt");
		$this->registerPseudoFilter("nth-child", "PseudoNthChild");
		$this->registerPseudoFilter("not", "PseudoNot", "selectorList");
		$this->registerPseudoFilter("has", "PseudoHas", "selectorList");
		$this->registerPseudoFilter("hasnt", "PseudoHasnt", "selectorList");
		$this->registerPseudoFilter("first-child", "PseudoFirstChild");
		$this->registerCombinator("", "Descendant");
		$this->registerCombinator(">", "Child");
		$this->registerCombinator("+", "Adjacent");
		$this->registerCombinator("~", "General");
		parent::__construct("");
	}
	
	function query($selectorList){
		return $this->parse($selectorList);
	}
	
	/**
	 * Registers a new user defined pseudo-filter.
	 * 
	 * <p>Example 1:</p>
	 * <pre>
	 * // is the node in penultimate position?
	 * $selector->registerPseudoFilter(
	 *	 "penultimate", function ($node, $input, $position, $items
	 * ) {
	 *	 return $position == count($items) - 2;
	 * });
	 * $items = $selector->query('item:penultimate');
	 * </pre>
	 * 
	 * <p>Example 2:</p>
	 * <pre>
	 * // is node position a Fibonacci number?
	 * $css->registerPseudoFilter(
	 *	 "fibonacci", function ($node, $input, $position, $items
	 * ) {
	 *	 $is_fibonacci = false;
	 *	 if ($position > 0) {
	 *		 $n = sqrt(5 * pow($position, 2) + 4);
	 *		 $is_fibonacci = $n - floor($n) == 0;
	 *		 if (!$is_fibonacci) {
	 *			 $n = sqrt(5 * pow($position, 2) - 4);
	 *			 $is_fibonacci = $n - floor($n) == 0;
	 *		 }
	 *	 }
	 *	 return $is_fibonacci;
	 * });
	 * $items = $selector->query('item:fibonacci');
	 * </pre>
	 * 
	 * <p>Example 3:</p>
	 * <pre>
	 * // is the node position divisible by a given number?
	 * $css->registerPseudoFilter(
	 *	 "divisible", function ($node, $input, $position, $items
	 * ) {
	 *	 $n = intval($input);
	 *	 return $n > 0 && $position % $n == 0;
	 * });
	 * 
	 * // selects all nodes 'divisible' by 3
	 * $items = $selector->query('item:divisible(3)');
	 * </pre>
	 * 
	 * @param string		 $name   Pseudo-filter name
	 * @param string|Closure $object Class name or user defined function.
	 * @param string		 $entity Entity (default is 'value')
	 * 
	 * @return void
	 */
	function registerPseudoFilter($name, $object, $entity = "value"){
		if (is_callable($object))
			// user defined pseudo-filter
			$this->_pseudoFilters[$name] = [
				"classname" => "PseudoUserDefined",
				"user_def_function" => $object,
				"entity" => $entity
			];
		else
			$this->_pseudoFilters[$name] = [
				"classname" => $object,
				"user_def_function" => null,
				"entity" => $entity
			];
	}
	function registerCombinator($name, $object){
		if (is_callable($object))
			$this->_combinators[$name] = [
				"classname" => "UserDefined",
				"user_def_function" => $object
			];
		else
			$this->_combinators[$name] = [
				"classname" => $object,
				"user_def_function" => null
			];
	}
	protected function combinator(){
		$ret = false;
		$combinatorNames = array_keys($this->_combinators);
		
		if (list($name) = $this->in($combinatorNames)) {
			$combinator = $this->_combinators[$name];
			$ret = Factory::getInstance(
				$combinator["classname"], $combinator["user_def_function"]
			);
		}
		return $ret;
	}
	protected function attrOperator(){
		return $this->in(Attr::getOperators());
	}
	protected function identifier(){
		if (list($id) = $this->match(CssParser::IDENTIFIER))
			return [$id];
		return false;
	}	
	protected function value(){
		/** ex:
		 * 'hello'
		 * "hello\"man"
		 * 'hello\'man'
		 * 0015blah
		 * _blah_
		 */
		if (   !(list($value) = $this->str())
			&& !(list($value) = $this->number())
			&& !(list($value) = $this->match(CssParser::IDENTIFIER))
		)
			return false;
		return [$value];
	}
	protected function pseudoFilter(){
		if (!$this->match("/^\:/"))
			return false;
		if (!list($name) = $this->is("identifier"))
			throw new TextParserException("Invalid identifier".$this->_node->exceptionContext(), $this);
		$filter = array_key_exists($name, $this->_pseudoFilters)? $this->_pseudoFilters[$name] : null;
		if ($filter === null)
			throw new TextParserException("Unknown pseudo-filter".$this->_node->exceptionContext(), $this);
		$input = "";
		if ($this->eq("(")){
			if (!$input = $this->is($filter["entity"]))
				throw new TextParserException("Invalid input".$this->_node->exceptionContext(), $this);
			if (is_array($input))
				$input = $input[0];
			if (!$this->eq(")"))
				throw new TextParserException("Invalid expression".$this->_node->exceptionContext(), $this);
		}
		$pseudoFilter = PseudoFactory::getInstance(
			$filter["classname"], $input, $filter["user_def_function"]
		);
		return $pseudoFilter;
	}
	protected function attrFilter(){
		$attrName = "";
		$op = "";
		$value = "";
		if (!$this->match("/^\[/"))
			return false;
		if (!list($attrName) = $this->is("identifier"))
			throw new TextParserException("Invalid identifier".$this->_node->exceptionContext(), $this);
		if (list($op) = $this->is("attrOperator")) {
			if (!list($value) = $this->is("value"))
				throw new TextParserException("Invalid attribute operator".$this->_node->exceptionContext(), $this);
		}
		if (!$this->eq("]"))
			throw new TextParserException("Invalid expression".$this->_node->exceptionContext(), $this);
		return new Attr($attrName, $op, $value);
	}
	protected function idFilter(){
		$id = "";
		if (!$this->match("/^\#/"))
			return false;
		if (!list($id) = $this->is("identifier"))
			throw new TextParserException("Invalid identifier".$this->_node->exceptionContext(), $this);
		return new Id($id);
	}
	protected function classFilter(){
		$className = "";
		if (!$this->match("/^\./"))
			return false;
		if (!list($className) = $this->is("identifier"))
			throw new TextParserException("Invalid identifier".$this->_node->exceptionContext(), $this);
		return new _Class($className);
	}
	protected function filter(){
		$filter = null;
		
		if (   (!$filter = $this->is("classFilter"))
			&& (!$filter = $this->is("idFilter"))
			&& (!$filter = $this->is("attrFilter"))
			&& (!$filter = $this->is("pseudoFilter"))
		) {
			return false;
		}
		return $filter;
	}
	protected function element(){
		$element = null;
		$filter = null;
		$tagName = "*";
		$this->match("\s+");
		if ((list($name) = $this->eq("*"))||(list($name) = $this->is("identifier")))
			$tagName = $name? $name : "*";
		elseif (!$filter = $this->is("filter"))
			return false;
		$element = new Element($tagName);
		if($filter)
			$element->addFilter($filter);
		while($filter = $this->is("filter"))
			$element->addFilter($filter);
		return $element;
	}
	protected function factor(){
		$combinator = null;
		if ($combinator = $this->is("combinator")) {
			if (!$element = $this->is("element"))
				throw new TextParserException("Invalid expression".$this->_node->exceptionContext(), $this);
		}
		elseif ($element = $this->is("element"))
			$combinator = Factory::getInstance("Descendant");
		else
			return false;
		return new Factor($combinator, $element);
	}
	protected function selector(){
		$factor = null;
		if (!$factor = $this->is("factor"))
			return false;
		$selector = new Selector();
		$selector->addFactor($factor);
		while ($factor = $this->is("factor"))
			$selector->addFactor($factor);
		return $selector;
	}
	protected function selectorList(){
		$nodes = [];
		do {
			if (!$selector = $this->is("selector"))
				break;
			$nodes = array_merge(
				$nodes,
				$selector->filter($this->_node)
			);
		} while ($this->eq(","));
		return new ArrayObject(array_filter($nodes, function($obj){
			static $idList = array();
			if(in_array($obj,$idList,true)){
				return false;
			}
			$idList[] = $obj;
			return true;
		}));
	}
	protected function _parse(){
		return $this->is("selectorList");
	}
}