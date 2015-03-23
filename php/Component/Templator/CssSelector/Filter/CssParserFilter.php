<?php
namespace Surikat\Component\Templator\CssSelector\Filter;
abstract class CssParserFilter{
	abstract function match($node, $position, $items);
}