<?php
namespace Wild\Templix\CssSelector\Combinator;
interface CombinatorInterface{
	function filter($node, $tagname);
}