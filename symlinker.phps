<?php
if(!is_dir('redcat')&&is_dir('../redcat'))
	symlink('../redcat','redcat');