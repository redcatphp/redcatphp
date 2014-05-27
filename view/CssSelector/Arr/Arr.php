<?php
/**
 * This file contains the Arr class.
 * 
 * PHP Version 5.3
 * 
 * @category Tools_And_Utilities
 * @package  Arr
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw.github.com/soloproyectos/core/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/core
 */
namespace surikat\view\CssSelector\Arr;

/**
 * Class Arr.
 * 
 * @category Tools_And_Utilities
 * @package  Arr
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw.github.com/soloproyectos/core/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/core
 */
class Arr
{
    
    /**
     * Gets an attribute from a given array.
     * 
     * @param array  $arr     Array object
     * @param string $name    Attribute name
     * @param mixed  $default Default value (default is "")
     * 
     * @return mixed
     */
    public static function get($arr, $name, $default = "")
    {
        return array_key_exists($name, $arr)? $arr[$name] : $default;
    }
    
    /**
     * Sets an attribute.
     * 
     * @param array  $arr   Array object (passed by reference)
     * @param string $name  Attribute name
     * @param mixed  $value Value
     * 
     * @return void
     */
    public static function set(&$arr, $name, $value)
    {
        $arr[$name] = $value;
    }
    
    /**
     * Does the attribute exist?
     * 
     * @param array  $arr  Array object
     * @param string $name Attribute name
     * 
     * @return boolean
     */
    public static function exist($arr, $name)
    {
        return array_key_exists($name, $arr);
    }
    
    /**
     * Deletes an attribute.
     * 
     * @param array  $arr  Array object (passed by reference)
     * @param string $name Attribute name
     * 
     * @return void
     */
    public static function del(&$arr, $name)
    {
        if (array_key_exists($name, $arr)) {
            unset($arr[$name]);
        }
    }
}
