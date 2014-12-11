<?php namespace Surikat\Route;
interface Route {
	function __construct($match=null,$Controller=null);
	function match($url);
	function setController($Controller);
}