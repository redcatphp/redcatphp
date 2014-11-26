<?php namespace Surikat\Control; 
class HTTP{
public static function getallheaders(){ //for ngix compatibility
	if(function_exists('getallheaders')){
		return call_user_func_array('getallheaders',func_get_args());
	}
	$headers = '';
	foreach($_SERVER as $name=>$value){
		if(substr($name, 0, 5)=='HTTP_'){
			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		}
	}
	return $headers;
}
public static function getRealIpAddr(){
    return !empty($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
}
public static function nocacheHeaders(){
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
	header("Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" );
	header("Pragma: no-cache");
	header("Cache-Control: no-cache");
	header("Expires: -1");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Cache-Control: no-store, no-cache, must-revalidate");
}
static function fix_magic_quotes(){
	if(get_magic_quotes_gpc()){
		$strip_slashes_deep = function ($value) use (&$strip_slashes_deep) {
			return is_array($value) ? array_map($strip_slashes_deep, $value) : stripslashes($value);
		};
		$_GET = array_map($strip_slashes_deep, $_GET);
		$_POST = array_map($strip_slashes_deep, $_POST);
		$_COOKIE = array_map($strip_slashes_deep, $_COOKIE);
		$_REQUEST = array_map($strip_slashes_deep, $_REQUEST);
	}
}
static $codes = [
	505=>'HTTP Version Not Supported',
	504=>'Gateway Timeout',
	503=>'Service Unavailable',
	502=>'Bad Gateway',
	501=>'Not Implemented',
	500=>'Internal Server Error',
	417=>'Expectation Failed',
	416=>'Requested Range Not Satisfiable',
	415=>'Unsupported Media Type',
	414=>'Request-URI Too Long',
	413=>'Request Entity Too Large',
	412=>'Precondition Failed',
	411=>'Length Required',
	410=>'Gone',
	409=>'Conflict',
	408=>'Request Timeout',
	407=>'Proxy Authentication Required',
	406=>'Not Acceptable',
	405=>'Method Not Allowed',
	404=>'Not Found',
	403=>'Forbidden',
	402=>'Payment Required', //available in future
	401=>'Unauthorized',
	400=>'Bad Request',
	307=>'Temporary Redirect',
	306=>'', //unused
	305=>'Use Proxy',
	304=>'Not Modified',
	303=>'See Other',
	302=>'Found',
	301=>'Moved Permanently',
	206=>'Partial Content',
	205=>'Reset Content',
	204=>'No Content',
	203=>'Non-Authoritative Information',
	202=>'Accepted',
	201=>'Created',
	200=>'OK',
	101=>'Switching Protocols',
	100=>'Continue',
];
public static function code($n=505){
	if(headers_sent()) return false;
	if(!isset(self::$codes[$n])) $n = 500;
	header($_SERVER['SERVER_PROTOCOL'].' '.$n.' '.self::$codes[$n]);
	return true;

}
public static function verifPlageIP($IP,$PlageIP){
	//$ip=$_SERVER['REMOTE_ADDR'];
	//$plage_ip=array('deb'=>'192.168.120.1','fin'=>'192.168.120.253');
	//echo var_dump(verifPlageIP($ip,$plage_ip));
	$result=TRUE;
	$tabIP=explode(".",$IP);
	if(is_array($PlageIP)){
		foreach($PlageIP as $valeur){
			$tabPlageIP[]=explode(".",$valeur);
		}
		for($i=0;$i<4;$i++){
			if(($tabIP[$i]<$tabPlageIP[0][$i]) || ($tabIP[$i]>$tabPlageIP[1][$i])){
				$result=FALSE;
			}
		}
	}
	else{
		$tabPlageIP=explode(".",$PlageIP);	
		for($i=0;$i<4;$i++){
			if(($tabIP[$i]!=$tabPlageIP[$i])){
				$result=FALSE;
			}
		}
	}
	return ($result);		
}

// * Helper functions for manually starting and then ending the
// * connection. By using this you can end the session later whilst
// * the script is still running and so allow server side processing
// * to continue.
// * 
// * On the downside this ignores user aborting of loading the page.
// * 
// * Note that startNewHTTPHeader must be called before headers are sent.

// * This must be called before headers are sent!
// * 
// * This ends the default header and starts a new one.
// * This new one can be ended at any time using endHTTPHeader.

public static function startNewHTTPHeader(){
	ob_end_clean();
	header( "Connection: close \r\n");
	header("Content-Encoding: none\r\n");
	ignore_user_abort( true ); // optional
	ob_start();
}
public static function streamer_flv($file,$seekat=0){
	//Effacement du cache
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	// Ajout des bon headers
	header("Content-Type: video/x-flv");
	if($seekat != 0) {
	   print("FLV");
	   print(pack('C', 1 ));
	   print(pack('C', 1 ));
	   print(pack('N', 9 ));
	   print(pack('N', 9 ));
	}
	$fh = fopen($file, "rb");
	fseek($fh, $seekat);
	while (!feof($fh)) {
	   print (fread($fh, 16384));
	}
	fclose($fh);
}

 // * Note that this must be called after startNewHTTPHeader!
 // * 
 // * Ends the header (and so the connection) setup with the user.
 // * All HTTP and echo'd text will not be sent after calling this.
 // * This allows you to continue performing processing on server side.
public static function endHTTPHeader(){
	// now end connection
	header( "Content-Length: " . ob_get_length() );
	ob_end_flush();
	flush();
	ob_end_clean();
}
public static function endOutput($endMessage){
    ignore_user_abort(true);
    set_time_limit(0);
    header("Connection: close");
    header("Content-Length: ".strlen($endMessage));
    echo $endMessage;
    echo str_repeat("\r\n", 10); // just to be sure
    flush();
}

public static function stripWWW(){
	if(preg_match('/^www.(.+)$/i', $_SERVER['HTTP_HOST'], $matches)){
		header("Status: 301 Move permanently", false, 301);
		header('Location: http://'.$matches[1].$_SERVER['REQUEST_URI']);
		exit;
	}
}
public static function forceSSL($ssl=true){
	if($ssl){
		if(!isset($_SERVER['HTTPS'])||$_SERVER['HTTPS']!='on'){
			header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
	else{
		if(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'){
			header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
}
public static function requestheader($key){
	$headers = self::getallheaders();
	if(func_num_args()>1)
		return isset($headers[$key])&&$headers[$key]==func_get_arg(1);
	else
		return isset($headers[$key])?$headers[$key]:false;
}
public static function apache_request_headers(){
	if(function_exists('apache_request_headers')){
		return call_user_func_array('apache_request_headers',func_get_args());
	}
	$headers = [];
	foreach($_SERVER as $key => $value) {
		if(substr($key, 0, 5) == 'HTTP_') {
			$headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
		}
	}
	return $headers;
}
public static function FileEtag($file){
	$s = stat($file);
	return sprintf('%x-%s', $s['size'], base_convert(str_pad($s['mtime'], 16, "0"),10,16));
}
public static function reArrange(&$arr){
    $new = [];
	if(
		isset($arr['name'])
		&&isset($arr['type'])
		&&isset($arr['size'])
		&&isset($arr['tmp_name'])
		&&isset($arr['error'])
	){
		if(is_array($arr['name'])){
			foreach(array_keys($arr['name']) as $k){
				if(is_array($arr['name'][$k])){
					foreach(array_keys($arr['name'][$k]) as $key){
						$new[] = [
							'name'		=>&$arr['name'][$k][$key],
							'type'		=>&$arr['type'][$k][$key],
							'size'		=>&$arr['size'][$k][$key],
							'tmp_name'	=>&$arr['tmp_name'][$k][$key],
							'error'		=>&$arr['error'][$k][$key],
						];
					}
				}
				else{
					$new[] = [
						'name'		=>&$arr['name'][$k],
						'type'		=>&$arr['type'][$k],
						'size'		=>&$arr['size'][$k],
						'tmp_name'	=>&$arr['tmp_name'][$k],
						'error'		=>&$arr['error'][$k],
					];
				}
			}
		}
		else{
			$new[] = $arr;
		}
	}
	else{
		foreach($arr as &$a){
			$new = array_merge($new,self::reArrange($a));
		}
	}
    return $new;
}
static $canGzip = null;
static function checkCanGzip(){
	if(self::$canGzip===null){
		if(headers_sent()){
			self::$canGzip = 0;
		}
		elseif(isset($_SERVER['HTTP_ACCEPT_ENCODING'])&&strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false){
			self::$canGzip = "x-gzip";
		}
		elseif(isset($_SERVER['HTTP_ACCEPT_ENCODING'])&&strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false){
			self::$canGzip = "gzip";
		}
		else{
			self::$canGzip = 0;
		}
	}
	return self::$canGzip;
}
public static function basic_authentication(\Closure $authenticate,$realm='Authenticate'){
	// echo '<pre>';var_dump(getenv('HTTP_AUTHORIZATION'));exit;
	
	//set http auth headers for apache+php-cgi work around
	if(isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
		list($name, $password) = explode(':', base64_decode($matches[1]));
		$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
		$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
	}

	//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
	if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
		list($name, $password) = explode(':', base64_decode($matches[1]));
		$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
		$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
	}
	
	if(!isset($_SERVER['PHP_AUTH_USER'])||!$_SERVER['PHP_AUTH_USER']||true!==$authenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])){
		header('WWW-Authenticate: Basic realm="'.$realm.'"');
		header('HTTP/1.0 401 Unauthorized');
		return false;
	}
	return true;
}
public static function setup_php_http_auth() {
	// attempt to support PHP_AUTH_USER & PHP_AUTH_PW if they aren't supported in this SAPI
	//   known SAPIs that do support them:  apache, litespeed
    if ((PHP_SAPI === 'apache') || (PHP_SAPI === 'litespeed') || isset($_SERVER['PHP_AUTH_USER'])) {
        return;
    }
    foreach (['HTTP_AUTHORIZATION', 'AUTHORIZATION', 'REMOTE_USER'] as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            list($type, $encoded) = explode(' ', $_SERVER[$key]);
            break;
        }
    }
    if (!isset($type) || ($type !== 'Basic')) {
        return;
    }
    list($user, $pass) = explode(':', base64_decode($encoded));
    $_SERVER['PHP_AUTH_USER'] = $user;
    $_SERVER['PHP_AUTH_PW'] = $pass;
}
//Mobile Device Detect - This code have been made by Simon Boudrias ( http://simonboudrias.com ) It's inspire by a code by Andy Moore ( http://detectmobilebrowsers.mobi/ ) So, that's its. No license, and do whatever you want. There's no garantee whatsoever this code gonna work.
static function mobile_redirect($settings){
    $iphone=true;
    $ipod=true;
    $ipad=false;
    $android=true;
    $opera=true;
    $blackberry=true;
    $palm=true;
    $windows=true;
    $others=true;
    $mobileredirect=false;
    $desktopredirect=false;
    foreach($settings as $k=>$v){
		$$k = $v;
    }
    $mobile_browser = false;
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $accept = $_SERVER['HTTP_ACCEPT'];
    $basic = false;
    $basicMobile = [
		"ipad"=>"/ipad/i",
		"ipod"=>"/ipod/i",
		"iphone"=>"/iphone/i",
		"android"=>"/android/i",
		"blackberry"=>"/blackberry/i",
		"opera"=>"/opera mini/i",
		"palm"=>"/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i",
		"windows"=>"/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i",
		"others"=>"/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i"
	];
    foreach($basicMobile as $k=>$v){
        if(preg_match($v,$user_agent)){
            $mobile_browser = $$k; // mobile browser is either true or false depending on the setting of var when calling the function
            if(substr($$k,0,4)=='http'){ // does the value of the var resemble a url
              $mobile_browser = true; // setting true to mobile browser
              $mobileredirect = $$k; // set the mobile redirect url to the url value stored in the var value
            } // ends the if for var being a url
            $basic = true;
            break;
        }
    }
    if(!$basic){
        switch(true){
            case((strpos($accept,'text/vnd.wap.wml')>0)||(strpos($accept,'application/vnd.wap.xhtml+xml')>0)); // is the device showing signs of support for text/vnd.wap.wml or application/vnd.wap.xhtml+xml
				$mobile_browser = true; // set mobile browser to true
			break; // break out and skip the rest if we've had a match on the content accept headers
			case (isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])); // is the device giving us a HTTP_X_WAP_PROFILE or HTTP_PROFILE header - only mobile devices would do this
				$mobile_browser = true; // set mobile browser to true
			break; // break out and skip the final step if we've had a return true on the mobile specfic headers
			case (in_array(strtolower(substr($user_agent,0,4)),['1207'=>'1207','3gso'=>'3gso','4thp'=>'4thp','501i'=>'501i','502i'=>'502i','503i'=>'503i','504i'=>'504i','505i'=>'505i','506i'=>'506i','6310'=>'6310','6590'=>'6590','770s'=>'770s','802s'=>'802s','a wa'=>'a wa','acer'=>'acer','acs-'=>'acs-','airn'=>'airn','alav'=>'alav','asus'=>'asus','attw'=>'attw','au-m'=>'au-m','aur '=>'aur ','aus '=>'aus ','abac'=>'abac','acoo'=>'acoo','aiko'=>'aiko','alco'=>'alco','alca'=>'alca','amoi'=>'amoi','anex'=>'anex','anny'=>'anny','anyw'=>'anyw','aptu'=>'aptu','arch'=>'arch','argo'=>'argo','bell'=>'bell','bird'=>'bird','bw-n'=>'bw-n','bw-u'=>'bw-u','beck'=>'beck','benq'=>'benq','bilb'=>'bilb','blac'=>'blac','c55/'=>'c55/','cdm-'=>'cdm-','chtm'=>'chtm','capi'=>'capi','cond'=>'cond','craw'=>'craw','dall'=>'dall','dbte'=>'dbte','dc-s'=>'dc-s','dica'=>'dica','ds-d'=>'ds-d','ds12'=>'ds12','dait'=>'dait','devi'=>'devi','dmob'=>'dmob','doco'=>'doco','dopo'=>'dopo','el49'=>'el49','erk0'=>'erk0','esl8'=>'esl8','ez40'=>'ez40','ez60'=>'ez60','ez70'=>'ez70','ezos'=>'ezos','ezze'=>'ezze','elai'=>'elai','emul'=>'emul','eric'=>'eric','ezwa'=>'ezwa','fake'=>'fake','fly-'=>'fly-','fly_'=>'fly_','g-mo'=>'g-mo','g1 u'=>'g1 u','g560'=>'g560','gf-5'=>'gf-5','grun'=>'grun','gene'=>'gene','go.w'=>'go.w','good'=>'good','grad'=>'grad','hcit'=>'hcit','hd-m'=>'hd-m','hd-p'=>'hd-p','hd-t'=>'hd-t','hei-'=>'hei-','hp i'=>'hp i','hpip'=>'hpip','hs-c'=>'hs-c','htc '=>'htc ','htc-'=>'htc-','htca'=>'htca','htcg'=>'htcg','htcp'=>'htcp','htcs'=>'htcs','htct'=>'htct','htc_'=>'htc_','haie'=>'haie','hita'=>'hita','huaw'=>'huaw','hutc'=>'hutc','i-20'=>'i-20','i-go'=>'i-go','i-ma'=>'i-ma','i230'=>'i230','iac'=>'iac','iac-'=>'iac-','iac/'=>'iac/','ig01'=>'ig01','im1k'=>'im1k','inno'=>'inno','iris'=>'iris','jata'=>'jata','java'=>'java','kddi'=>'kddi','kgt'=>'kgt','kgt/'=>'kgt/','kpt '=>'kpt ','kwc-'=>'kwc-','klon'=>'klon','lexi'=>'lexi','lg g'=>'lg g','lg-a'=>'lg-a','lg-b'=>'lg-b','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-f'=>'lg-f','lg-g'=>'lg-g','lg-k'=>'lg-k','lg-l'=>'lg-l','lg-m'=>'lg-m','lg-o'=>'lg-o','lg-p'=>'lg-p','lg-s'=>'lg-s','lg-t'=>'lg-t','lg-u'=>'lg-u','lg-w'=>'lg-w','lg/k'=>'lg/k','lg/l'=>'lg/l','lg/u'=>'lg/u','lg50'=>'lg50','lg54'=>'lg54','lge-'=>'lge-','lge/'=>'lge/','lynx'=>'lynx','leno'=>'leno','m1-w'=>'m1-w','m3ga'=>'m3ga','m50/'=>'m50/','maui'=>'maui','mc01'=>'mc01','mc21'=>'mc21','mcca'=>'mcca','medi'=>'medi','meri'=>'meri','mio8'=>'mio8','mioa'=>'mioa','mo01'=>'mo01','mo02'=>'mo02','mode'=>'mode','modo'=>'modo','mot '=>'mot ','mot-'=>'mot-','mt50'=>'mt50','mtp1'=>'mtp1','mtv '=>'mtv ','mate'=>'mate','maxo'=>'maxo','merc'=>'merc','mits'=>'mits','mobi'=>'mobi','motv'=>'motv','mozz'=>'mozz','n100'=>'n100','n101'=>'n101','n102'=>'n102','n202'=>'n202','n203'=>'n203','n300'=>'n300','n302'=>'n302','n500'=>'n500','n502'=>'n502','n505'=>'n505','n700'=>'n700','n701'=>'n701','n710'=>'n710','nec-'=>'nec-','nem-'=>'nem-','newg'=>'newg','neon'=>'neon','netf'=>'netf','noki'=>'noki','nzph'=>'nzph','o2 x'=>'o2 x','o2-x'=>'o2-x','opwv'=>'opwv','owg1'=>'owg1','opti'=>'opti','oran'=>'oran','p800'=>'p800','pand'=>'pand','pg-1'=>'pg-1','pg-2'=>'pg-2','pg-3'=>'pg-3','pg-6'=>'pg-6','pg-8'=>'pg-8','pg-c'=>'pg-c','pg13'=>'pg13','phil'=>'phil','pn-2'=>'pn-2','pt-g'=>'pt-g','palm'=>'palm','pana'=>'pana','pire'=>'pire','pock'=>'pock','pose'=>'pose','psio'=>'psio','qa-a'=>'qa-a','qc-2'=>'qc-2','qc-3'=>'qc-3','qc-5'=>'qc-5','qc-7'=>'qc-7','qc07'=>'qc07','qc12'=>'qc12','qc21'=>'qc21','qc32'=>'qc32','qc60'=>'qc60','qci-'=>'qci-','qwap'=>'qwap','qtek'=>'qtek','r380'=>'r380','r600'=>'r600','raks'=>'raks','rim9'=>'rim9','rove'=>'rove','s55/'=>'s55/','sage'=>'sage','sams'=>'sams','sc01'=>'sc01','sch-'=>'sch-','scp-'=>'scp-','sdk/'=>'sdk/','se47'=>'se47','sec-'=>'sec-','sec0'=>'sec0','sec1'=>'sec1','semc'=>'semc','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','sk-0'=>'sk-0','sl45'=>'sl45','slid'=>'slid','smb3'=>'smb3','smt5'=>'smt5','sp01'=>'sp01','sph-'=>'sph-','spv '=>'spv ','spv-'=>'spv-','sy01'=>'sy01','samm'=>'samm','sany'=>'sany','sava'=>'sava','scoo'=>'scoo','send'=>'send','siem'=>'siem','smar'=>'smar','smit'=>'smit','soft'=>'soft','sony'=>'sony','t-mo'=>'t-mo','t218'=>'t218','t250'=>'t250','t600'=>'t600','t610'=>'t610','t618'=>'t618','tcl-'=>'tcl-','tdg-'=>'tdg-','telm'=>'telm','tim-'=>'tim-','ts70'=>'ts70','tsm-'=>'tsm-','tsm3'=>'tsm3','tsm5'=>'tsm5','tx-9'=>'tx-9','tagt'=>'tagt','talk'=>'talk','teli'=>'teli','topl'=>'topl','hiba'=>'hiba','up.b'=>'up.b','upg1'=>'upg1','utst'=>'utst','v400'=>'v400','v750'=>'v750','veri'=>'veri','vk-v'=>'vk-v','vk40'=>'vk40','vk50'=>'vk50','vk52'=>'vk52','vk53'=>'vk53','vm40'=>'vm40','vx98'=>'vx98','virg'=>'virg','vite'=>'vite','voda'=>'voda','vulc'=>'vulc','w3c '=>'w3c ','w3c-'=>'w3c-','wapj'=>'wapj','wapp'=>'wapp','wapu'=>'wapu','wapm'=>'wapm','wig '=>'wig ','wapi'=>'wapi','wapr'=>'wapr','wapv'=>'wapv','wapy'=>'wapy','wapa'=>'wapa','waps'=>'waps','wapt'=>'wapt','winc'=>'winc','winw'=>'winw','wonu'=>'wonu','x700'=>'x700','xda2'=>'xda2','xdag'=>'xdag','yas-'=>'yas-','your'=>'your','zte-'=>'zte-','zeto'=>'zeto','acs-'=>'acs-','alav'=>'alav','alca'=>'alca','amoi'=>'amoi','aste'=>'aste','audi'=>'audi','avan'=>'avan','benq'=>'benq','bird'=>'bird','blac'=>'blac','blaz'=>'blaz','brew'=>'brew','brvw'=>'brvw','bumb'=>'bumb','ccwa'=>'ccwa','cell'=>'cell','cldc'=>'cldc','cmd-'=>'cmd-','dang'=>'dang','doco'=>'doco','eml2'=>'eml2','eric'=>'eric','fetc'=>'fetc','hipt'=>'hipt','http'=>'http','ibro'=>'ibro','idea'=>'idea','ikom'=>'ikom','inno'=>'inno','ipaq'=>'ipaq','jbro'=>'jbro','jemu'=>'jemu','java'=>'java','jigs'=>'jigs','kddi'=>'kddi','keji'=>'keji','kyoc'=>'kyoc','kyok'=>'kyok','leno'=>'leno','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-g'=>'lg-g','lge-'=>'lge-','libw'=>'libw','m-cr'=>'m-cr','maui'=>'maui','maxo'=>'maxo','midp'=>'midp','mits'=>'mits','mmef'=>'mmef','mobi'=>'mobi','mot-'=>'mot-','moto'=>'moto','mwbp'=>'mwbp','mywa'=>'mywa','nec-'=>'nec-','newt'=>'newt','nok6'=>'nok6','noki'=>'noki','o2im'=>'o2im','opwv'=>'opwv','palm'=>'palm','pana'=>'pana','pant'=>'pant','pdxg'=>'pdxg','phil'=>'phil','play'=>'play','pluc'=>'pluc','port'=>'port','prox'=>'prox','qtek'=>'qtek','qwap'=>'qwap','rozo'=>'rozo','sage'=>'sage','sama'=>'sama','sams'=>'sams','sany'=>'sany','sch-'=>'sch-','sec-'=>'sec-','send'=>'send','seri'=>'seri','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','siem'=>'siem','smal'=>'smal','smar'=>'smar','sony'=>'sony','sph-'=>'sph-','symb'=>'symb','t-mo'=>'t-mo','teli'=>'teli','tim-'=>'tim-','tosh'=>'tosh','treo'=>'treo','tsm-'=>'tsm-','upg1'=>'upg1','upsi'=>'upsi','vk-v'=>'vk-v','voda'=>'voda','vx52'=>'vx52','vx53'=>'vx53','vx60'=>'vx60','vx61'=>'vx61','vx70'=>'vx70','vx80'=>'vx80','vx81'=>'vx81','vx83'=>'vx83','vx85'=>'vx85','wap-'=>'wap-','wapa'=>'wapa','wapi'=>'wapi','wapp'=>'wapp','wapr'=>'wapr','webc'=>'webc','whit'=>'whit','winw'=>'winw','wmlb'=>'wmlb','xda-'=>'xda-',])); // check against a list of trimmed user agents to see if we find a match
				$mobile_browser = true; // set mobile browser to true
			break; // break even though it's the last statement in the switch so there's nothing to break away from but it seems better to include it than exclude it
			default;
				$mobile_browser = false; // set mobile browser to false
			break; // break even though it's the last statement in the switch so there's nothing to break away from but it seems better to include it than exclude it
        }
    }
    if($redirect = ($mobile_browser) ? $mobileredirect : $desktopredirect){
        header('Location: '.$redirect); // redirect to the right url for this device
        exit;
    }
}
}