<?php
namespace surikat\view\CssSelector\Filter;
abstract class CssParserFilter{
	abstract function match($node, $position, $items);
}