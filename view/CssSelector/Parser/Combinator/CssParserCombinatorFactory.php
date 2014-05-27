<?php
/**
 * This file contains the CssParserCombinatorFactory class.
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
use surikat\view\CssSelector\Parser\Combinator\CssParserCombinator;

/**
 * Class CssParserCombinatorFactory.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserCombinatorFactory
{
    /**
     * Gets a combinator instance by class name.
     * 
     * @param string  $classname       Class name
     * @param Closure $userDefFunction User defined function (not required)
     * 
     * @return CssParserCombinator
     */
    public static function getInstance($classname, $userDefFunction = null)
    {
        $class = "surikat\\view\\CssSelector\\Parser\\Combinator\\"
            . $classname;
        return new $class($userDefFunction);
    }
}
