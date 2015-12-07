<?php
namespace Pharborist;

/**
 * Formatter factory.
 */
class FormatterFactory {

  /**
   * @var Formatter
   */
  protected static $defaultFormatter;

  /**
   * Get the default formatter.
   *
   * The default formatter is used by node builders.
   *
   * @return Formatter
   */
  public static function getDefaultFormatter() {
    if (!static::$defaultFormatter) {
      static::$defaultFormatter = static::getDrupalFormatter();
    }
    return static::$defaultFormatter;
  }

  /**
   * Set the default formatter.
   *
   * @param Formatter $formatter
   */
  public static function setDefaultFormatter(Formatter $formatter) {
    static::$defaultFormatter = $formatter;
  }

  /**
   * Create formatter using the specified config file.
   *
   * @param string $filename
   *
   * @return Formatter
   */
  public static function createFormatter($filename) {
    $config = json_decode(file_get_contents($filename), TRUE);
    return new Formatter($config['formatter']);
  }

  public static function getDrupalFormatter() {
    //return static::createFormatter(dirname(__DIR__) . '/config/drupal.json');
    return new Formatter(json_decode('{
  "formatter": {
    "nl": "\n",
    "indent": 2,
    "soft_limit": 80,
    "boolean_null_upper": true,
    "force_array_new_style": true,
    "else_newline": true,
    "declaration_brace_newline": false,
    "list_keep_wrap": false,
    "list_wrap_if_long": false,
    "blank_lines_around_class_body": 1
  }
}',true));
  }

  public static function getPsr2Formatter() {
    //return static::createFormatter(dirname(__DIR__) . '/config/psr2.json');
    return new Formatter(json_decode('{
  "formatter": {
    "nl": "\n",
    "indent": 4,
    "soft_limit": 80,
    "boolean_null_upper": false,
    "force_array_new_style": true,
    "else_newline": false,
    "declaration_brace_newline": true,
    "list_keep_wrap": true,
    "list_wrap_if_long": true,
    "blank_lines_around_class_body": 0
  }
}',true));
  }

  /**
   * Format a node using the default formatter.
   *
   * @param Node $node
   *   Node to format.
   */
  public static function format(Node $node) {
    static::getDefaultFormatter()->format($node);
  }
}
