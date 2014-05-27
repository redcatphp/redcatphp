<?php
/**
 * This file contains the CssParserCombinatorAdjacent class.
 * 
 * PHP Version 5.3
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
namespace surikat\view\CssSelector\Parser\Combinator;

use surikat\view\CssSelector\Parser\CssParserHelper;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;

/**
 * Class CssParserCombinatorAdjacent.
 * 
 * This class represents a filter in a CSS expression.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserCombinatorAdjacent extends CssParserCombinator
{
    
    /**
     * Gets the adjacent node.
     * 
     * @param DOMElement $node    DOMElement object
     * @param string     $tagname Tag name
     * 
     * @return array of DOMElement
     */
    public function filter($node, $tagname)
    {
        $ret = array();
        if ($element = CssParserHelper::getNextSiblingElement($node)) {
            array_push($ret, $element);
        }
        return $ret;
    }
}
