<?php
namespace Unit;
class Router {
    private $view;
    private $controller;
    function __construct(View $view, $controller = null){
        $this->view = $view;
        $this->controller = $controller;
    }
    function getView(){
        return $this->view;
    }
    function getController(){
        return $this->controller;
    }
}