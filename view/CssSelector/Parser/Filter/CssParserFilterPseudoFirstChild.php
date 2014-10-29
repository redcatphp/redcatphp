<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoFirstChild extends CssParserFilterPseudo{
    public function match($node, $position, $items){
        return !$node->previousSibling;
    }
}