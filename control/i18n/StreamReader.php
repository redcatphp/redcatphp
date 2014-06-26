<?php namespace surikat\control\i18n;
// Simple class to wrap file streams, string streams, etc. seek is essential, and it should be byte stream
class StreamReader {
  function read($bytes){
    return false;
  }
  function seekto($position){
    return false;
  }
  function currentpos(){
    return false;
  }
  function length(){
    return false;
  }
}
