<?php
namespace Wild\Localize\Gettext\Extractors;

use Wild\Localize\Gettext\Translations;
use Wild\Localize\Gettext\Utils\JsFunctionsScanner;

/**
 * Class to get gettext strings from javascript files
 */
class JsCode extends Extractor implements ExtractorInterface
{
    public static $functions = array(
        '__' => '__',
        'n__' => 'n__',
        'p__' => 'p__',
    );

    /**
     * {@inheritDoc}
     */
    public static function fromString($string, Translations $translations = null, $file = '')
    {
        if ($translations === null) {
            $translations = new Translations();
        }

        $functions = new JsFunctionsScanner($string);
        $functions->saveGettextFunctions(self::$functions, $translations, $file);
    }
}
