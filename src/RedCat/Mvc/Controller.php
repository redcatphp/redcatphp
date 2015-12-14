<?php
namespace RedCat\Mvc;
use RedCat\Wire\Di;
abstract class Controller {
	protected $model;
	protected $di;
    function __construct($model = null, Di $di=null){
        $this->model = $model;
        $this->di = $di;
    }
}