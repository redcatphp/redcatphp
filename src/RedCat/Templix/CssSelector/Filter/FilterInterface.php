<?php
namespace RedCat\Templix\CssSelector\Filter;
interface FilterInterface{
	function match($node, $position, $items);
}