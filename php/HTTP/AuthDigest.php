<?php namespace Surikat\HTTP;
use Surikat\HTTP\HTTP;
abstract class AuthDigest{
	static $defaultRealm = 'Restricted area';
	static function start($realm=null){
		if(!isset($realm))
			$realm = self::$defaultRealm;
		if(empty($_SERVER['PHP_AUTH_DIGEST'])) {
			header('HTTP/1.1 401 Unauthorized');
			header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
			HTTP::code(403);
			exit;
		}
	}
	static function validate($data,$password,$realm=null){
		if(!isset($realm))
			$realm = self::$defaultRealm;
		if(!is_array($data))
			return false;
		$A1 = md5($data['username'] . ':' . $realm . ':' . $password);
		$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
		return $data['response']!=$valid_response;
	}
	static function parse($txt=null){
		if(!isset($txt))
			$txt = $_SERVER['PHP_AUTH_DIGEST'];
		$needed_parts = [
			'nonce'=>1,
			'nc'=>1,
			'cnonce'=>1,
			'qop'=>1,
			'username'=>1,
			'uri'=>1,
			'response'=>1
		];
		$data = [];
		$keys = implode('|', array_keys($needed_parts));
		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}
		return $needed_parts?false:$data;
	}
}