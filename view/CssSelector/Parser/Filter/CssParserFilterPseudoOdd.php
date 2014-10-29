<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;
class CssParserFilterPseudoOdd extends CssParserFilterPseudo{
    public function match($node, $position, $items){
        return $position % 2 > 0;
    }
}