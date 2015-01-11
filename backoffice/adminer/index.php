<?php
function adminer_object() {
    // autoloader
    include_once "./plugins/plugin.php";
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }
    class AdminerSurikat extends AdminerPlugin {   
		function permanentLogin() {
		  return "c01993cf7c861ff2c8a43421840bee10"; // key used for permanent login
		}   
		/*
		function name() {
		  return 'Software'; // custom name in title and heading
		}
		function credentials() {
		  return array('localhost', 'ODBC', ''); // server, username and password for connecting to database
		}  
		function database() {
		  return 'software'; // database name, will be escaped by Adminer
		}
		function login($login, $password) {
		  return ($login == 'admin' && $password == ''); // validate user submitted credentials
		}  
		function tableName($tableStatus) {
		  return h($tableStatus["Comment"]); // tables without comments would return empty string and will be ignored by Adminer
		}
		function fieldName($field, $order = 0) {
		  return ($order <= 5 && !preg_match('~_(md5|sha1)$~', $field["field"]) ? h($field["comment"]) : ""); // only columns with comments will be displayed and only the first five in select
		}
		*/
	}
    $plugins = array(
        // specify enabled plugins here
        new AdminerFrames,
        new AdminerVersionNoverify,
        //new AdminerDumpXml,
        //new AdminerTinymce,
        //new AdminerFileUpload("data/"),
        //new AdminerSlugify,
        //new AdminerTranslation,
        //new AdminerForeignSystem,
    );  
    return new AdminerSurikat($plugins);
}

// include original Adminer or Adminer Editor
include "./adminer.php";