<?php
namespace Wild\Mvc;
use Wild\Wire\Di;
abstract class Controller {
	protected $model;
	protected $di;
    function __construct($model = null, Di $di=null){
        $this->model = $model;
        $this->di = $di;
    }
}