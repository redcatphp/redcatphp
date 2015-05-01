<?php
namespace Templix\CssSelector\Filter;
abstract class CssParserFilter{
	abstract function match($node, $position, $items);
}