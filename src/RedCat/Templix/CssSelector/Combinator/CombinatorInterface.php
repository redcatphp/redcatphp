<?php
namespace RedCat\Templix\CssSelector\Combinator;
interface CombinatorInterface{
	function filter($node, $tagname);
}