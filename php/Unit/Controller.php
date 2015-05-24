<?php
namespace Unit;
class Controller {
	private $model;
    function __construct($model = null){
        $this->model = $model;
    }
}