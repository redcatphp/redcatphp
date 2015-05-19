<?php
namespace Unit;
class DiCallback {
	private $str;
	public function __construct($str) {
		$this->str = $str;
	}
	public function run(\Unit\DiContainer $dic) {
		$parts = explode('::', trim($this->str, '{}'));
		$object = $dic->create(array_shift($parts));
		while ($var = array_shift($parts)) {
			if (strpos($var, '(') !== false) {
				$args = explode(',', substr($var, strpos($var, '(')+1, strpos($var, ')')-strpos($var, '(')-1));
				$object = call_user_func_array([$object, substr($var, 0, strpos($var, '('))], ($args[0] == null) ? [] : $args);
			}
			else $object = $object->$var;
		}
		return $object;
	}
}