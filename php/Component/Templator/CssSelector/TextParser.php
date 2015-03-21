<?php
namespace Surikat\Templator\CssSelector;
use Surikat\Templator\CssSelector\TextParserException;
use Surikat\Templator\CssSelector\TextTokenizer;
abstract class TextParser extends TextTokenizer{
	const UNGREEDY = 0x4;
	private $_target;
	private $_flags;
	function __construct($target, $flags = 0){
		$this->_target = $target;
		$this->_flags = $flags;
		if ($this->_target instanceof Parser) {
			parent::__construct($target->getString(), $flags);
			$this->offset = $target->getOffset();
		} else {
			parent::__construct($target, $flags);
		}
	}
	abstract protected function _parse();
	function parse($string = ""){
		$this->offset = 0;
		$this->string = func_num_args() > 0 ? $string : $this->string;
		$ungreedy = TextParser::UNGREEDY & $this->_flags;
		$ret = $this->_parse();
		if ($ret) {
			if ($this->_target instanceof TextParser) {
				$this->_target->setOffset($this->offset);
			} elseif (!$ungreedy && !$this->end()) {
				throw new TextParserException("Unrecognized expression", $this);
			}
		}
		return $ret;
	}
	protected function is($methodName /*, $arg1, $arg2, $arg3 ... */){
		if (!method_exists($this, $methodName))
			throw new TextParserException("The method `$methodName` does not exist");
		if (!is_callable([$this, $methodName]))
			throw new TextParserException("The method `$methodName` is inaccessible");
		$offset = $this->offset; // saves offset
		$ret = call_user_func_array([$this, $methodName],array_slice(func_get_args(), 1));// calls user function
		if (!$ret)
			$this->offset = $offset;		// restores offset
		return $ret;
	}
}