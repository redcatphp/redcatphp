<?php
namespace Unit;
class View {
	private $model;
	private $template;
    function __construct($model = null, $template = null){
        $this->model = $model;
        $this->template = $template;
    }
    function getModel(){
		return $this->model;
	}
	function getTemplate(){
		return $this->template;
	}
	function display(){
        $template = $this->model->getTemplate();
        return $this->template->display($template);
    }
}