<?php
namespace surikat\view\CssSelector\Parser\Filter;
class CssParserFilterPseudoFactory{
    public static function getInstance($classname, $input = "", $userDefFunction = null){
        $fullname = "surikat\\view\\CssSelector\\Parser\\Filter\\".$classname;
        return new $fullname($input, $userDefFunction);
    }
}