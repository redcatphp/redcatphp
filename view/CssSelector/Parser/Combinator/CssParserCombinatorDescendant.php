<?php
/**
 * This file contains the CssParserCombinatorDescendant class.
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
 * Class CssParserCombinatorDescendant.
 * 
 * This class represents a filter in a CSS expression.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserCombinatorDescendant extends CssParserCombinator
{
    
    /**
     * Gets the descendant nodes.
     * 
     * @param DOMElement $node    DOMElement object
     * @param string     $tagname Tag name
     * 
     * @return array of DOMElement
     */
    public function filter($node, $tagname)
    {
        return CssParserHelper::getElementsByTagName($node, $tagname);
    }
}
