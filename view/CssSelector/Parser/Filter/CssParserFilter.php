<?php
namespace surikat\view\CssSelector\Parser\Filter;
abstract class CssParserFilter{
	abstract public function match($node, $position, $items);
}