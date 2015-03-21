<?php
namespace Surikat\Templator\CssSelector;
use Surikat\Templator\CssSelector\TextParser;
use Exception;
class TextParserException extends Exception{
	private $_parser;
	function __construct($message, $parser = null){
		$this->_parser = $parser;
		parent::__construct($message);
	}
	function getPrintableMessage(){
		$ret = $this->message;
		if ($this->_parser != null) {
			$string = rtrim($this->_parser->getString());
			$offset = $this->_parser->getOffset();
			
			$rightStr = substr($string, $offset);
			$offset0 = $offset + strlen($rightStr) - strlen(ltrim($rightStr));
			
			$str1 = substr($string, 0, $offset0);
			$offset1 = strrpos($str1, "\n");
			if ($offset1 !== false) {
				$offset1++;
			}
			
			$str2 = substr($string, $offset1);
			$offset2 = strpos($str2, "\n");
			if ($offset2 === false) {
				$offset2 = strlen($str2);
			}
			
			$str3 = substr($str2, 0, $offset2);
			$line = $offset0 > 0? substr_count($string, "\n", 0, $offset0) : 0;
			$column = $offset0 - $offset1;
			
			$ret = "$str3\n" . str_repeat(" ", $column) . "^" . $this->message;
			if ($line > 0) {
				$ret .= " (line " . ($line + 1) . ")";
			}
		}
		
		return $ret;
	}
	function __toString(){
		return __CLASS__ . ":\n\n" . $this->getPrintableMessage() . "\n";
	}
}