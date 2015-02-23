<?php namespace Surikat\Service;
use Surikat\User\Session;
use Surikat\User\Auth;
use Surikat\DependencyInjection\Container;
class ServiceAuth {
	static function infos(){
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		echo json_encode(Container::get('User\Session')->get('_AUTH_'));
	}
	static function email(){
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		echo json_encode(Container::get('User\Session')->get('_AUTH_','email'));
	}
	static function persona(){
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
			if(($js = json_decode($response))&&$js->status==='okay'&&$js->email)
				Container::get('User\Session')->set('email',$js->email);
		}
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		echo $response;
	}
	static function logout(){
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('Pragma: no-cache');
		Container::get('User\Session')->destroy();
		echo 'ok';
	}
}