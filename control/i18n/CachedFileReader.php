<?php namespace surikat\control\i18n;
// Preloads entire file in memory first, then creates a StringReader over it (it assumes knowledge of StringReader internals)
class CachedFileReader extends StringReader {
  function __construct($filename) {
    if (file_exists($filename)) {

      $length=filesize($filename);
      $fd = fopen($filename,'rb');

      if (!$fd) {
        $this->error = 3; // Cannot read file, probably permissions
        return false;
      }
      $this->_str = fread($fd, $length);
      fclose($fd);

    } else {
      $this->error = 2; // File doesn't exist
      return false;
    }
  }
}