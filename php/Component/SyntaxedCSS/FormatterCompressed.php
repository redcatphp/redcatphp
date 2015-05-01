<?php namespace SyntaxedCSS;
/**
 * SCSS compressed formatter
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 */
class FormatterCompressed extends Formatter {
	public $open = "{";
	public $tagSeparator = ",";
	public $assignSeparator = ":";
	public $break = "";

	public function indentStr($n = 0) {
		return "";
	}
}