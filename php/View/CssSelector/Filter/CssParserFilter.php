<?php
namespace Surikat\View\CssSelector\Filter;
abstract class CssParserFilter{
	abstract function match($node, $position, $items);
}