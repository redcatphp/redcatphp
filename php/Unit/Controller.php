<?php
namespace Unit;
abstract class Controller {
	protected $model;
	protected $di;
    function __construct($model = null, Di $di){
        $this->model = $model;
        $this->di = $di;
    }
}