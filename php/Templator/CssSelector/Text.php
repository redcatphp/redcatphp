<?php
namespace Surikat\Templator\CssSelector;
abstract class Text{
	static function isEmpty($str){
		return $str === null || is_string($str) && strlen($str) == 0;
	}
	static function concat($glue){
		$ret = "";
		$args = [];
		$len = func_num_args();
		for ($i = 1; $i < $len; $i++) {
			$value = func_get_arg($i);
			$values = is_array($value)? array_values($value) : [$value];
			$args = array_merge($args, $values);
		}
		foreach ($args as $arg) {
			if (Text::isempty($arg))
				continue;
			if (strlen($ret) > 0)
				$ret = rtrim($ret, $glue) . $glue;
			$ret .= $arg;
		}
		return $ret;
	}
	static function trimText($str){
		$ret = "";
		$str = preg_replace("/\t/", "	", $str);
		$lines = explode("\n", $str);
		$len = count($lines);
		// start index.
		// ignores initial empty lines.
		$i0 = 0;
		for ($i = 0; $i < $len; $i++) {
			$line = $lines[$i];
			$trimLine = trim($line);
			if (strlen($trimLine) > 0) {
				$i0 = $i;
				break;
			}
		}
		// final index.
		// ignores final empty lines.
		$i1 = count($lines) - 1;
		for ($i = $len - 1; $i >= 0; $i--) {
			$line = $lines[$i];
			$trimLine = trim($line);
			if (strlen($trimLine) > 0) {
				$i1 = $i;
				break;
			}
		}
		// calculates spaces to remove
		$spaces = PHP_INT_MAX;
		for ($i = $i0; $i <= $i1; $i++) {
			$line = $lines[$i];
			$leftTrimLine = ltrim($line);
			$spaces = min($spaces, strlen($line) - strlen($leftTrimLine));
		}
		// removes left spaces
		for ($i = $i0; $i <= $i1; $i++) {
			$line = $lines[$i];
			$ret = Text::concat("\n", $ret, substr($line, $spaces));
		}
		return $ret;
	}
}