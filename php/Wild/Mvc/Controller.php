<?php
namespace Wild\Mvc;
use Wild\Kinetic\Di;
abstract class Controller {
	protected $model;
	protected $di;
    function __construct($model = null, Di $di=null){
        $this->model = $model;
        $this->di = $di;
    }
}