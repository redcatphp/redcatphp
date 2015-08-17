<?php
namespace Wild\Templix\CssSelector\Filter;
interface FilterInterface{
	function match($node, $position, $items);
}