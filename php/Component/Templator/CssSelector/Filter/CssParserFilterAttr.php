<?php
namespace Surikat\Templator\CssSelector\Filter;
use Surikat\Templator\CssSelector\Filter\CssParserFilter;
class CssParserFilterAttr extends CssParserFilter{
	const EQUAL_SELECTOR = '=';
	const NOT_EQUAL_SELECTOR = '!=';
	const CONTAIN_SELECTOR = '*=';
	const CONTAIN_WORD_SELECTOR = '~=';
	const CONTAIN_PREFIX_SELECTOR = '|=';
	const START_WITH_SELECTOR = '^=';
	const END_WITH_SELECTOR = '$=';
	private static $_operators = [
		self::EQUAL_SELECTOR,
		self::NOT_EQUAL_SELECTOR,
		self::CONTAIN_SELECTOR,
		self::CONTAIN_WORD_SELECTOR,
		self::CONTAIN_PREFIX_SELECTOR,
		self::START_WITH_SELECTOR,
		self::END_WITH_SELECTOR
	];
	private $_attrName;
	private $_op;
	private $_value;
	function __construct($attrName, $op, $value){
		$this->_attrName = $attrName;
		$this->_op = $op;
		$this->_value = $value;
	}
	static function getOperators(){
		return self::$_operators;
	}
	private function _isEqualSelector($node){
		return $node->hasAttribute($this->_attrName)
			&& $node->getAttribute($this->_attrName) == $this->_value;
	}
	private function _isNotEqualSelector($node){
		return !$node->hasAttribute($this->_attrName)
			|| $node->getAttribute($this->_attrName) != $this->_value;
	}
	private function _isContainSelector($node){
		if ($node->hasAttribute($this->_attrName)) {
			$attr = $node->getAttribute($this->_attrName);
			$len = strlen($this->_value);
			if ($len > 0) {
				$pos = strpos($attr, $this->_value);
				return $pos !== false;
			}
		}
		return false;
	}
	private function _isContainWordSelector($node){
		if ($node->hasAttribute($this->_attrName)) {
			$items = explode(" ", trim($node->getAttribute($this->_attrName)));
			foreach ($items as $item) {
				if (preg_match("/^\w+$/", $item) && $this->_value == $item) {
					return true;
				}
			}
		}
		
		return false;
	}
	private function _isContainPrefixSelector($node){
		if ($node->hasAttribute($this->_attrName)) {
			$attr = $node->getAttribute($this->_attrName);
			$len = strlen($this->_value);
			if ($len > 0) {
				$pos = stripos($attr, $this->_value);
				return $pos === 0 && (strlen($attr) <= $len || $attr[$len] == "-");
			}
		}
		
		return false;
	}
	private function _isStartWithSelector($node){
		if ($node->hasAttribute($this->_attrName) && strlen($this->_value) > 0) {
			$attrValue = $node->getAttribute($this->_attrName);
			return strpos($attrValue, $this->_value) === 0;
		}
		return false;
	}
	private function _isEndWithSelector($node){
		if ($node->hasAttribute($this->_attrName)) {
			$len = strlen($this->_value);
			if ($len > 0) {
				$attr = $node->getAttribute($this->_attrName);
				$attrLen = strlen($attr);
				return $len <= $attrLen
					&& strpos($attr, $this->_value, $attrLen - $len) !== false;
			}
		}
		return false;
	}
	private function _hasAttribute($node){
		return $node->hasAttribute($this->_attrName);
	}
	private function _isAttrSelector($node){
		$ret = false;
		if ($this->_op == self::EQUAL_SELECTOR)
			$ret = $this->_isEqualSelector($node);
		elseif ($this->_op == self::NOT_EQUAL_SELECTOR)
			$ret = $this->_isNotEqualSelector($node);
		elseif ($this->_op == self::CONTAIN_SELECTOR)
			$ret = $this->_isContainSelector($node);
		elseif ($this->_op == self::CONTAIN_WORD_SELECTOR)
			$ret = $this->_isContainWordSelector($node);
		elseif ($this->_op == self::CONTAIN_PREFIX_SELECTOR)
			$ret = $this->_isContainPrefixSelector($node);
		elseif ($this->_op == self::START_WITH_SELECTOR)
			$ret = $this->_isStartWithSelector($node);
		elseif ($this->_op == self::END_WITH_SELECTOR)
			$ret = $this->_isEndWithSelector($node);
		else
			$ret = $this->_hasAttribute($node);
		return $ret;
	}
	function match($node, $position, $items){
		return $this->_isAttrSelector($node);
	}
}