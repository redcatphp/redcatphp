<?php namespace surikat\service;
use surikat\control\session;
class Service_Persona {
    protected $audience; //Scheme, hostname and port
	static function email(){
		session::start();
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		echo json_encode(isset($_SESSION['email'])?$_SESSION['email']:'');
	}
	static function login(){
		$response = '';
		if(isset($_POST['assertion'])){
			$assertion = $_POST['assertion'];
			$audience = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https://':'http://').$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
			$postdata = 'assertion='.urlencode($assertion).'&audience='.urlencode($audience);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://verifier.login.persona.org/verify");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			$response = curl_exec($ch);
			curl_close($ch);
			if($js = json_decode($response)){
				session::start();
				if($js->status==='okay'&&$js->email)
					$_SESSION['email'] = $js->email;
			}
		}
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		echo $response;
	}
	static function logout(){
		$_SESSION = array();
		session::start();
		session_destroy();
		session_write_close();
	}
}
