<?php
/**
 * This file contains the CssParserCombinatorUserDefined class.
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
use Closure;

use surikat\view\CssSelector\Parser\Exception\CssParserException;
use surikat\view\CssSelector\Parser\CssParserHelper;
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;

/**
 * Class CssParserCombinatorUserDefined.
 * 
 * This class represents a filter in a CSS expression.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserCombinatorUserDefined extends CssParserCombinator
{
    
    /**
     * User defined function.
     * @var Closure
     */
    private $_userDefFunction;
    
    /**
     * Constructor.
     * 
     * @param Closure $userDefFunction User defined function
     * 
     * @return void
     */
    public function __construct($userDefFunction)
    {
        $this->_userDefFunction = $userDefFunction;
    }
    
    /**
     * Gets the child nodes.
     * 
     * @param DOMElement $node    DOMElement object
     * @param string     $tagname Tag name
     * 
     * @return array of DOMElements
     */
    public function filter($node, $tagname)
    {
        $userDefFunction = $this->_userDefFunction;
        $nodes = $userDefFunction($node, $tagname);
        
        if (!is_array($nodes)) {
            throw new CssParserException(
                "The user defined combinator is not an array"
            );
        }
        
        // excludes the items that are not elements
        //$items = array();
        //foreach ($nodes as $node) {
            //if ($node instanceof \surikat\view\CORE) {
                //array_push($items, $node);
            //}
        //}
        
        return $items;
    }
}
