<?php
namespace Surikat\Templator\CssSelector;
class TextTokenizer{
	const OFFSET_CAPTURE = 0x1;
	const CASE_SENSITIVE = 0x4;
	const SEARCH_ANYWHERE = 0x8;
	const TOKEN = "\w+|.";
	const IDENTIFIER = "[a-z]\w*";
	const NUMBER = '[+-]?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][+-]?[0-9]+)?';
	const STRING = '(["\'])((?:\\\\\2|.)*?)\2';
	private $_flags;
	protected $string;
	protected $offset;
	function __construct($string, $flags = 0){
		$this->string = $string;
		$this->offset = 0;
		$this->_flags = $flags;
	}
	function eq($str, $flags = 0){
		$ret = false;
		if (list($str) = $this->match(preg_quote($str, "/"), $matches, $flags))
			$ret = [$str];
		return $ret;
	}
	function in($items, $flags = 0){
		$ret = false;
		
		// sorts the items in descending order according to their length
		usort(
			$items,
			function ($item1, $item2) {
				return strlen($item1) < strlen($item2);
			}
		);
		
		foreach ($items as $item) {
			if ($this->eq($item, $flags)) {
				$ret = [$item];
				break;
			}
		}
		
		return $ret;
	}
	function number($flags = 0){
		$ret = false;
		if ($number = $this->match(TextTokenizer::NUMBER, $matches, $flags))
			$ret = $number;
		return $ret;
	}
	function str($flags = 0){
		$ret = false;
		if ($this->match(TextTokenizer::STRING, $matches, $flags)) {
			$delimiter = $matches[2];
			$str = $matches[3];
			$str = str_replace("\\$delimiter", "$delimiter", $str);
			$ret = [$str];
		}
		return $ret;
	}
	function token(){
		$ret = false;
		if (list($token) = $this->match(TextTokenizer::TOKEN))
			$ret = [$token];
		return $ret;
	}
	function id(){
		$ret = false;
		if (list($id) = $this->match(TextTokenizer::IDENTIFIER))
			$ret = [$id];
		return $ret;
	}
	function match($regexp, &$matches = [], $flags = 0){
		// we do not like empty strings
		if (strlen($regexp) == 0) {
			return false;
		}
		
		$ret = false;
		$explicitRegexp = strlen($regexp) > 0 && $regexp[0] == "/";
		$substr = substr($this->string, $this->offset);
		
		if (!$explicitRegexp) {
			$caseSensitive  = TextTokenizer::CASE_SENSITIVE
				& ($this->_flags | $flags);
			$searchAnywhere = TextTokenizer::SEARCH_ANYWHERE
				& ($this->_flags | $flags);
			$modifiers = "us" . ($caseSensitive? "" : "i");
			$regexp = $searchAnywhere
				? "/($regexp)/$modifiers"
				: "/^\s*($regexp)/$modifiers";
		}
		
		if (preg_match($regexp, $substr, $matches, PREG_OFFSET_CAPTURE)) {
			$offsetCapture = TextTokenizer::OFFSET_CAPTURE
							  & ($this->_flags | $flags);
			$str = $matches[0][0];
			$offset = $matches[0][1] + strlen($str);
			
			if ($offsetCapture) {
				// fixes offsets
				foreach ($matches as $i => $match) {
					$matches[$i][1] += $this->offset;
				}
			} else {
				// ignores offsets
				foreach ($matches as $i => $match) {
					$matches[$i] = $matches[$i][0];
				}
			}
			
			if (!ctype_alnum($substr[$offset - 1])
				|| $offset == strlen($substr)
				|| !ctype_alnum($substr[$offset])
			) {
				$this->offset += $offset;
				$ret = [ltrim($str)];
			}
		}
		
		return $ret;
	}
	function getOffset(){
		return $this->offset;
	}
	function setOffset($value){
		$this->offset = $value;
	}
	function getString(){
		return $this->string;
	}
	function reset(){
		$this->offset = 0;
	}
	function end(){
		return $this->offset >= strlen(rtrim($this->string));
	}
}