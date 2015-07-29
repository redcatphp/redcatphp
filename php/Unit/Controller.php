<?php
namespace Unit;
abstract class Controller {
	protected $model;
    function __construct($model = null){
        $this->model = $model;
    }
}