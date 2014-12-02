<?php
require __DIR__.'/Loader.php';
(new Controller\Application())->run(@$_SERVER['PATH_INFO']);