<?php
namespace Unit\Route;
interface RouteInterface{
	function __invoke($uri);
	function getPath();
	function getParams();
}