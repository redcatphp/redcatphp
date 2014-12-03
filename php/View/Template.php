<?php namespace Surikat\View;
class Template {
	var $path;
    protected $vars = [];
    private $file;
    function __construct($path = '.') {
        $this->path = rtrim($path,'/').'/';
    }
    function get($key){
        return isset($this->vars[$key])?$this->vars[$key]:null;
    }
    function set($key, $value = null) {
        if(is_array($key)||is_object($key)){
            foreach ($key as $k => $v)
                $this->vars[$k] = $v;
        }
        else
            $this->vars[$key] = $value;
    }
    function has($key) {
        return isset($this->vars[$key]);
    }
    function clear($key = null){
        if (is_null($key))
            $this->vars = [];
        else
            unset($this->vars[$key]);
    }
    function render($file, $data = null) {
        $this->file = $this->getFile($file);
        if (!file_exists($this->file))
            throw new \Exception("Template file not found: {$this->file}.");
        if(is_array($data))
            $this->vars = array_merge($this->vars, $data);
        extract($this->vars);
        include $this->file;
    }
    function fetch($file, $data = null) {
        ob_start();
        $this->render($file, $data);
        $output = ob_get_clean();
        return $output;
    }
    function exists($file) {
        return is_file($this->getFile($file));
    }
    function getFile($file){
        if(!pathinfo($file, PATHINFO_EXTENSION))
            $file .= '.tml';
        if((substr($file,0,1)=='/'))
            return $file;
        else
            return $this->path.'/'.$file;
    }
}