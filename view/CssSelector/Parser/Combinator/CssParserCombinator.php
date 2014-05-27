<?php
/**
 * This file contains the CssParserCombinator class.
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


/**
 * Class CssParserCombinator.
 * 
 * This class represents a filter in a CSS expression.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
abstract class CssParserCombinator
{
    
    /**
     * Gets the nodes that combines with a given node and a tag name.
     * 
     * @param DOMElement $node    DOMElement object
     * @param string     $tagname Tag name
     * 
     * @return boolean
     */
    abstract public function filter($node, $tagname);
}
