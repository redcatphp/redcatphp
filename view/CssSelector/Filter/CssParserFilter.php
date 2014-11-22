<?php
namespace surikat\view\CssSelector\Filter;
abstract class CssParserFilter{
	abstract public function match($node, $position, $items);
}