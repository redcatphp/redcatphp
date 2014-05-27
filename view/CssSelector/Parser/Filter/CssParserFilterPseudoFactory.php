<?php
/**
 * This file contains the CssParserFilterPseudoFactory class.
 * 
 * PHP Version 5.3
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
namespace surikat\view\CssSelector\Parser\Filter;
use Closure;
use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;

/**
 * Class CssParserFilterPseudoFactory.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserFilterPseudoFactory
{
    
    /**
     * Gets a psuedo-filter instance by class name.
     * 
     * @param string  $classname       Class name
     * @param string  $input           String input (default is "")
     * @param Closure $userDefFunction Used defined function (not required)
     * 
     * @return CssParserFilterPseudo
     */
    public static function getInstance(
        $classname, $input = "", $userDefFunction = null
    ) {
        $fullname = "surikat\\view\\CssSelector\\Parser\\Filter\\"
            . $classname;
        return new $fullname($input, $userDefFunction);
    }
}
