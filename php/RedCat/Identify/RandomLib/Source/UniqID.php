<?php
/**
 * The UniqID Random Number Source
 *
 * This uses the internal `uniqid()` function to generate low strength random
 * numbers.
 *
 * PHP version 5.3
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

namespace RedCat\Identify\RandomLib\Source;

use RedCat\Identify\SecurityLib\Strength;
use RedCat\Identify\SecurityLib\Util;

/**
 * The UniqID Random Number Source
 *
 * This uses the internal `uniqid()` function to generate low strength random
 * numbers.
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @codeCoverageIgnore
 */
class UniqID implements \RedCat\Identify\RandomLib\Source {

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return Strength An instance of one of the strength classes
     */
    public static function getStrength() {
        return new Strength(Strength::LOW);
    }

    /**
     * Generate a random string of the specified size
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    public function generate($size) {
        $result = '';
        while (Util::safeStrlen($result) < $size) {
            $result = uniqid($result, true);
        }
        return Util::safeSubstr($result, 0, $size);
    }

}
