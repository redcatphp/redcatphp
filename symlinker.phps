<?php
if(!is_dir('surikat')&&is_dir('../surikat'))
	symlink('../surikat','surikat');