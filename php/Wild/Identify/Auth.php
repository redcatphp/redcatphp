<?php
/*
 * Auth - Complete Authentication System
 *
 * @package Identify
 * @version 1.3
 * @link http://github.com/surikat/Identify/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
namespace Wild\Identify;
use Wild\DataMap\B;
use Wild\DataMap\DataSource;
if (version_compare(phpversion(), '5.5.0', '<')){
	require_once __DIR__.'/password-compat.inc.php';
}
class Auth{
	
	const RIGHT_MANAGE = 2;
	const RIGHT_EDIT = 4;
	const RIGHT_MODERATE = 8;
	const RIGHT_POST = 16;
	
	const ROLE_ADMIN = 30;
	const ROLE_EDITOR = 4;
	const ROLE_MODERATOR = 8;
	const ROLE_MEMBER = 16;
	
	const ERROR_USER_BLOCKED = 1;
	const ERROR_USER_BLOCKED_2 = 46;
	const ERROR_USER_BLOCKED_3 = 47;
	const ERROR_LOGIN_SHORT = 2;
	const ERROR_LOGIN_LONG = 3;
	const ERROR_LOGIN_INCORRECT = 4;
	const ERROR_LOGIN_INVALID = 5;
	const ERROR_NAME_INVALID =  48;
	const ERROR_PASSWORD_SHORT = 6;
	const ERROR_PASSWORD_LONG = 7;
	const ERROR_PASSWORD_INVALID = 8;
	const ERROR_PASSWORD_NOMATCH = 9;
	const ERROR_PASSWORD_INCORRECT = 10;
	const ERROR_PASSWORD_NOTVALID = 11;
	const ERROR_NEWPASSWORD_SHORT = 12;
	const ERROR_NEWPASSWORD_LONG = 13;
	const ERROR_NEWPASSWORD_INVALID = 14;
	const ERROR_NEWPASSWORD_NOMATCH = 15;
	const ERROR_LOGIN_PASSWORD_INVALID = 16;
	const ERROR_LOGIN_PASSWORD_INCORRECT = 17;
	const ERROR_EMAIL_INVALID = 18;
	const ERROR_EMAIL_INCORRECT = 19;
	const ERROR_NEWEMAIL_MATCH = 20;
	const ERROR_ACCOUNT_INACTIVE = 21;
	const ERROR_SYSTEM_ERROR = 22;
	const ERROR_LOGIN_TAKEN = 23;
	const ERROR_EMAIL_TAKEN = 24;
	const ERROR_AUTHENTICATION_REQUIRED = 25;
	const ERROR_ALREADY_AUTHENTICATED = 26;
	const ERROR_RESETKEY_INVALID = 27;
	const ERROR_RESETKEY_INCORRECT = 28;
	const ERROR_RESETKEY_EXPIRED = 29;
	const ERROR_ACTIVEKEY_INVALID = 30;
	const ERROR_ACTIVEKEY_INCORRECT = 31;
	const ERROR_ACTIVEKEY_EXPIRED = 32;
	const ERROR_RESET_EXISTS = 33;
	const ERROR_ALREADY_ACTIVATED = 34;
	const ERROR_ACTIVATION_EXISTS = 35;
	const OK_PASSWORD_CHANGED = 36;
	const OK_EMAIL_CHANGED = 37;
	const OK_ACCOUNT_ACTIVATED = 38;
	const OK_ACCOUNT_DELETED = 39;
	const OK_LOGGED_IN = 40;
	const OK_LOGGED_OUT = 41;
	const OK_REGISTER_SUCCESS = 42;
	const OK_PASSWORD_RESET = 43;
	const OK_RESET_REQUESTED = 44;
	const OK_ACTIVATION_SENT = 45;

	public $siteUrl;
	private $db;
	private $right;
	protected $cost = 10;
	protected $Session;
	protected $Server;
	
	protected $rootLogin;
	protected $rootPassword;
	protected $rootName;
	protected $siteLoginUri;
	protected $siteActivateUri;
	protected $siteResetUri;
	protected $tableUsers;
	protected $tableRequests;
	protected $algo;
	protected $mailSendmail;
	protected $mailHost;
	protected $mailUsername;
	protected $mailPassword;
	protected $mailPort;
	protected $mailSecure;
	
	protected $rootPasswordNeedRehash;
	
	function __construct(Session $Session=null,
		$rootLogin = 'root',
		$rootPassword = 'root',
		$rootName	= 'Developer',
		$siteLoginUri = 'Login',
		$siteActivateUri = 'Signin',
		$siteResetUri ='Signin',
		$tableUsers = 'user',
		$tableRequests = 'request',
		$algo = PASSWORD_DEFAULT,
		$mailSendmail = true,
		$mailHost=null,
		$mailUsername=null,
		$mailPassword= null,
		$mailPort=25,
		$mailSecure='tls',
		DataSource $db = null
	){
		$this->rootLogin = $rootLogin;
		$this->rootPassword = $rootPassword;
		$this->rootName = $rootName;
		$this->siteLoginUri = $siteLoginUri;
		$this->siteActivateUri = $siteActivateUri;
		$this->siteResetUri = $siteResetUri;
		$this->tableUsers = $tableUsers;
		$this->tableRequests = $tableRequests;
		$this->algo = $algo;
		$this->mailSendmail = $mailSendmail;
		$this->mailHost = $mailHost;
		$this->mailUsername = $mailUsername;
		$this->mailPassword = $mailPassword;
		$this->mailPort = $mailPort;
		$this->mailSecure = $mailSecure;
		
		if(!$Session)
			$Session = new Session();
		$this->Session = $Session;
		if(!isset($db)&&class_exists('Wild\DataMap\B')){
			$this->db = B::getDatabase();
		}
		$this->siteUrl = $this->getBaseHref();
		$this->siteUrl = rtrim($this->siteUrl,'/').'/';
	}
	function getSession(){
		return $this->Session;
	}
	function rootPasswordNeedRehash(){
		return $this->rootPasswordNeedRehash;
	}
	function sendMail($email, $type, $key, $login){
		$fromName = isset($this->mailFromName)?$this->mailFromName:null;
		$fromEmail = isset($this->mailFromEmail)?$this->mailFromEmail:null;
		$replyName = isset($this->mailReplyName)?$this->mailReplyName:null;
		$replyEmail = isset($this->mailReplyEmail)?$this->mailReplyEmail:null;
		$siteLoginUri = isset($this->siteLoginUri)?$this->siteLoginUri:null;
		$siteActivateUri = isset($this->siteActivateUri)?$this->siteActivateUri:null;
		$siteResetUri = isset($this->siteResetUri)?$this->siteResetUri:null;
		
		if($type=="activation"){
			$subject = "{$fromName} - Account Activation";
			$message = "Account activation required : <strong><a href=\"{$this->siteUrl}{$siteActivateUri}?action=activate&key={$key}\">Activate my account</a></strong>";
		}
		else{
			$subject = "{$fromName} - Password reset request";
			$message = "Password reset request : <strong><a href=\"{$this->siteUrl}{$siteResetUri}?action=resetpass&key={$key}\">Reset my password</a></strong>";
		}
		return PHPMailer::mail([$email=>$login],$subject,$message);
	}
	public function loginRoot($password,$lifetime=0){
		$pass = $this->rootPassword;
		if(!$pass)
			return self::ERROR_SYSTEM_ERROR;
		$id = 0;
		if(strpos($pass,'$')!==0){
			if($pass!=$password){
				$this->Session->addAttempt();
				return self::ERROR_LOGIN_PASSWORD_INCORRECT;
			}
		}
		else{
			if(!($password&&password_verify($password, $pass))){
				$this->Session->addAttempt();
				return self::ERROR_LOGIN_PASSWORD_INCORRECT;
			}
			else{
				$options = ['cost' => $this->cost];
				if(password_needs_rehash($pass, $this->algo, $options)){
					$this->rootPassword = password_hash($password, $this->algo, $options);
					$this->rootPasswordNeedRehash = true;
				}
			}
		}
		if($this->db){
			if($this->db[$this->tableUsers]->exists())
				$id = $this->db->getCell('SELECT id FROM '.$this->db->escTable($this->tableUsers).' WHERE login = ?',[$this->rootLogin]);
			else
				$id = null;
			if(!$id){
				try{
					$user = $this->db
						->create($this->tableUsers,[
							'login'=>$this->rootLogin,
							'name'=>isset($this->rootName)?$this->rootName:$this->rootLogin,
							'email'=>isset($this->rootPasswordEmail)?$this->rootPasswordEmail:null,
							'active'=>1,
							'right'=>static::ROLE_ADMIN,
							'type'=>'root'
						])
					;
					$id = $user->id;
				}
				catch(\Exception $e){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		$this->addSession((object)[
			'id'=>$id,
			'login'=>$this->rootLogin,
			'name'=>isset($this->rootName)?$this->rootName:$this->rootLogin,
			'email'=>isset($this->email)?$this->email:null,
			'right'=>static::ROLE_ADMIN,
			'type'=>'root'
		],$lifetime);
		return self::OK_LOGGED_IN;
	}
	public function loginPersona($email,$lifetime=0){
		if($e=$this->validateEmail($email))
			return $e;
		$userDefault = [
			'login'=>$email,
			'name'=>$email,
			'email'=>$email,
			'type'=>'persona',
			'right'=>self::ROLE_MEMBER,
			'active'=>1,
		];
		if($this->db){
			$user = $this->db->findOne($this->tableUsers,' WHERE email = ? AND type = ?',[$email,'persona']);
			if(!$user){
				try{
					$user = $this->db->create($this->tableUsers,$userDefault);
				}
				catch(\Exception $e){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		else{
			$user = $userDefault;
			$user->id = $email;
		}
		$this->addSession($user,$lifetime);
		return self::OK_LOGGED_IN;
	}
	public function login($login, $password, $lifetime=0){
		if($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($login==$this->rootLogin)
			return $this->loginRoot($password,$lifetime);
		if(!ctype_alnum($login)&&filter_var($login,FILTER_VALIDATE_EMAIL)){
			if($this->db[$this->tableUsers]->exists())
				$login = $this->db->getCell('SELECT login FROM '.$this->db->escTable($this->tableUsers).' WHERE email = ?',[$login]);
			else
				$login = null;
		}
		if($e=($this->validateLogin($login)||$this->validatePassword($password))){
			$this->Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INVALID;
		}
		$uid = $this->getUID($login);
		if(!$uid){
			$this->Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INCORRECT;
		}
		$user = $this->getUser($uid);
		if(!($password&&password_verify($password, $user->password))){
			$this->Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INCORRECT;
		}
		else{
			$options = ['salt' => $user->salt, 'cost' => $this->cost];
			if(password_needs_rehash($user->password, $this->algo, $options)){
				$password = password_hash($password, $this->algo, $options);
				$row = $this->db->read($this->tableUsers,(int)$user->id);
				$row->password = $password;
				try{
					$this->db->put($row);
				}
				catch(\Exception $e){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		if(!isset($user->active)||$user->active!=1){
			$this->Session->addAttempt();
			return self::ERROR_ACCOUNT_INACTIVE;
		}
		if(!$this->addSession($user,$lifetime)){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_LOGGED_IN;
	}

	public function register($email, $login, $password, $repeatpassword, $name=null){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(!$name)
			$name = $login;
		if($e=$this->validateEmail($email))
			return $e;
		if($e=$this->validateLogin($login))
			return $e;
		if($e=$this->validateDisplayname($name))
			return $e;
		if($e=$this->validatePassword($password))
			return $e;
		if($password!==$repeatpassword){
			return self::ERROR_PASSWORD_NOMATCH;
		}
		if($this->isEmailTaken($email)){
			$this->Session->addAttempt();
			return self::ERROR_EMAIL_TAKEN;
		}
		if($this->isLoginTaken($login)){
			$this->Session->addAttempt();
			return self::ERROR_LOGIN_TAKEN;
		}
		$this->addUser($email, $password, $login, $name);
		return self::OK_REGISTER_SUCCESS;
	}
	public function activate($key){
		if($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(strlen($key) !== 40){
			$this->Session->addAttempt();
			return self::ERROR_ACTIVEKEY_INVALID;
		}
		$getRequest = $this->getRequest($key, "activation");
		$user = $this->getUser($getRequest[$this->tableUsers.'_id']);
		if(isset($user->active)&&$user->active==1){
			$this->Session->addAttempt();
			$this->deleteRequest($getRequest['id']);
			return self::ERROR_SYSTEM_ERROR;
		}
		$row = $this->db->read($this->tableUsers,(int)$getRequest[$this->tableUsers.'_id']);
		$row->active = 1;
		$this->db->put($row);
		$this->deleteRequest($getRequest['id']);
		return self::OK_ACCOUNT_ACTIVATED;
	}
	public function requestReset($email){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $e;
		if($this->db[$this->tableUsers]->exists())
			$id = $this->db->getCell('SELECT id FROM '.$this->db->escTable($this->tableUsers).' WHERE email = ?',[$email]);
		else
			$id = null;
		if(!$id){
			$this->Session->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if($e=$this->addRequest($id, $email, 'reset')){
			$this->Session->addAttempt();
			return $e;
		}
		return self::OK_RESET_REQUESTED;
	}
	public function logout(){
		if($this->connected()&&$this->Session->destroy()){
			return self::OK_LOGGED_OUT;
		}
		return $this->Session->destroy();
	}
	public function getHash($string, $salt){
		return password_hash($string, $this->algo, ['salt' => $salt, 'cost' => $this->cost]);
	}
	public function getUID($login){
		if($this->db[$this->tableUsers]->exists())
			return $this->db->getCell('SELECT id FROM '.$this->db->escTable($this->tableUsers).' WHERE login = ?',[$login]);
	}
	private function addSession($user,$lifetime=0){
		$this->Session->setCookieLifetime($lifetime);
		$this->Session->setKey($user->id);
		$this->Session->set('_AUTH_',(array)$user);
		return true;
	}
	private function isEmailTaken($email){
		if($this->db[$this->tableUsers]->exists())
			return !!$this->db->getCell('SELECT id FROM '.$this->db->escTable($this->tableUsers).' WHERE email = ?',[$email]);
	}
	private function isLoginTaken($login){
		return !!$this->getUID($login);
	}
	private function addUser($email, $password, $login=null, $name=null){
		try{
			$row = $this->db->create($this->tableUsers,[]);
		}
		catch(\Exception $e){
			return self::ERROR_SYSTEM_ERROR;
		}
		$uid = $row->id;
		if($e=$this->addRequest($uid, $email, "activation")){
			$row->trash();
			return $e;
		}
		$salt = substr(strtr(base64_encode(\mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)), '+', '.'), 0, 22);
		$password = $this->getHash($password, $salt);
		if(!$login)
			$login = $email;
		if(!$name)
			$name = $login;
		$row->login = $login;
		$row->name = $name;
		$row->email = $email;
		$row->password = $password;
		$row->salt = $salt;
		$row->right = self::ROLE_MEMBER;
		$row->type = 'local';
		try{
			$this->db->create($row);
		}
		catch(\Exception $e){
			$this->db->delete($row);
			return self::ERROR_SYSTEM_ERROR;
		}
	}

	public function getUser($uid){
		return $this->db->read($this->tableUsers,(int)$uid);
	}

	public function deleteUser($uid, $password){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validatePassword($password)){
			$this->Session->addAttempt();
			return $e;
		}
		$getUser = $this->getUser($uid);
		if(!($password&&password_verify($password, $getUser['password']))){
			$this->Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		$row = $this->db->read($this->tableUsers,(int)$uid);
		if(!$row->trash()){
			return self::ERROR_SYSTEM_ERROR;
		}
		$this->Session->destroyKey($uid);
		foreach($row->own($this->tableRequests) as $request){
			if(!$request->trash()){
				return self::ERROR_SYSTEM_ERROR;
			}
		}		
		return self::OK_ACCOUNT_DELETED;
	}
	private function addRequest($uid, $email, $type){
		if($type != "activation" && $type != "reset"){
			return self::ERROR_SYSTEM_ERROR;
		}
		$row = $this->db->findOne($this->tableRequests,' WHERE '.$this->db->safeColumn($this->tableUsers.'_id').' = ? AND type = ?',[$uid, $type]);
		if($row){
			$this->deleteRequest($row->id);
		}
		$user = $this->getUser($uid);
		if($type == "activation" && isset($user->active) && $user->active == 1){
			return self::ERROR_ALREADY_ACTIVATED;
		}
		$key = (new RandomLib\Factory())->getMediumStrengthGenerator()->generate(40);
		$expire = date("Y-m-d H:i:s", strtotime("+1 day"));
		$request = [
			'_type'=>$this->tableRequests,
			'_one_'.$this->tableUsers.'_x_'=>$user,
			'rkey'=>$key,
			'expire'=>$expire,
			'type'=>$type
		];
		try{
			$this->db->put($user);
		}
		catch(\Exception $e){
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!$this->sendMail($email, $type, $key, $user->name)){
			return self::ERROR_SYSTEM_ERROR;
		}
	}
	private function getRequest($key, $type){
		$row = $this->db->findOne($this->tableRequests,' WHERE rkey = ? AND type = ?',[$key, $type]);
		if(!$row){
			$this->Session->addAttempt();
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_INCORRECT;
			elseif($type=='reset')
				return self::ERROR_RESETKEY_INCORRECT;
			return;
		}
		$expiredate = strtotime($row->expire);
		$currentdate = strtotime(date("Y-m-d H:i:s"));
		if ($currentdate > $expiredate){
			$this->Session->addAttempt();
			$this->deleteRequest($row->id);
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_EXPIRED;
			elseif($type=='reset')
				return self::ERROR_ACTIVEKEY_EXPIRED;
		}
		return [
			'id' => $row->id,
			$this->tableUsers.'_id' => $row->{'_one_'.$this->tableUsers}->id
		];
	}
	private function deleteRequest($id){
		return $this->db->exec('DELETE FROM '.$this->db->escTable($this->tableRequests).' WHERE id = ?',[$id]);
	}
	public function validateLogin($login){
		if (strlen($login) < 1)
			return self::ERROR_LOGIN_SHORT;
		elseif (strlen($login) > 30)
			return self::ERROR_LOGIN_LONG;
		elseif(!ctype_alnum($login)&&!filter_var($login, FILTER_VALIDATE_EMAIL))
			return self::ERROR_LOGIN_INVALID;
	}
	public function validateDisplayname($login){
		if (strlen($login) < 1)
			return self::ERROR_NAME_INVALID;
		elseif (strlen($login) > 50)
			return self::ERROR_NAME_INVALID;
	}
	private function validatePassword($password){
		if (strlen($password) < 6)
			return self::ERROR_PASSWORD_SHORT;
		elseif (strlen($password) > 72)
			return self::ERROR_PASSWORD_LONG;
		elseif ((!preg_match('@[A-Z]@', $password) && !preg_match('@[a-z]@', $password)) || !preg_match('@[0-9]@', $password))
			return self::ERROR_PASSWORD_INVALID;
	}
	private function validateEmail($email){
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return self::ERROR_EMAIL_INVALID;
	}
	public function resetPass($key, $password, $repeatpassword){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(strlen($key) != 40){
			return self::ERROR_RESETKEY_INVALID;
		}
		if($e=$this->validatePassword($password))
			return $e;
		if($password !== $repeatpassword){ // Passwords don't match
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$data = $this->getRequest($key, "reset");
		$user = $this->getUser($data[$this->tableUsers.'_id']);
		if(!$user){
			$this->Session->addAttempt();
			$this->deleteRequest($data['id']);
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!($password&&password_verify($password, $user->password))){
			$password = $this->getHash($password, $user->salt);
			$row = $this->db->read($this->tableUsers,$data[$this->tableUsers.'_id']);
			$row->password = $password;
			try{
				$this->db->put($row);
			}
			catch(\Exception $e){
				return self::ERROR_SYSTEM_ERROR;
			}
		}
		$this->deleteRequest($data['id']);
		return self::OK_PASSWORD_RESET;
	}
	public function resendActivation($email){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $r;
		$row = $this->db->findOne($this->tableUsers,' WHERE email = ?',[$email]);
		if(!$row){
			$this->Session->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if(isset($row->active)&&$row->active == 1){
			$this->Session->addAttempt();
			return self::ERROR_ALREADY_ACTIVATED;
		}
		if($e=$this->addRequest($row->id, $email, "activation")){
			$this->Session->addAttempt();
			return $e;
		}
		return self::OK_ACTIVATION_SENT;
	}
	public function changePassword($uid, $currpass, $newpass, $repeatnewpass){
		if ($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validatePassword($currpass)){
			$this->Session->addAttempt();
			return $e;
		}
		if($e=$this->validatePassword($newpass))
			return $e;
		if($newpass !== $repeatnewpass){
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$user = $this->getUser($uid);
		if(!$user){
			$this->Session->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		$newpass = $this->getHash($newpass, $user->salt);
		if(!($password&&password_verify($currpass, $user->password))){
			$this->Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if($currpass != $newpass){			
			$row = $this->db->read($this->tableUsers,(int)$uid);
			$row->password = $newpass;
			$this->db->put($row);
		}
		return self::OK_PASSWORD_CHANGED;
	}
	public function getEmail($uid){
		$row = $this->db->read($this->tableUsers,(int)$uid);
		if (!$row->id){
			return false;
		}
		return $row->email;
	}
	public function changeEmail($uid, $email, $password){
		if($s=$this->Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $e;
		if($e=$this->validatePassword($password))
			return $e;
		$user = $this->getUser($uid);
		if(!$user){
			$this->Session->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!($password&&password_verify($password, $user->password))){
			$this->Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if ($email == $user->email){
			$this->Session->addAttempt();
			return self::ERROR_NEWEMAIL_MATCH;
		}
		$row = $this->db->read($this->tableUsers,(int)$uid);
		$row->email = $email;
		try{
			$this->db->put($row);
		}
		catch(\Exception $e){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_EMAIL_CHANGED;
	}
	function getBaseHref(){
		if(isset($this->siteUrl)&&$this->siteUrl)
			return $this->siteUrl;
		$protocol = 'http'.(isset($_SERVER["HTTPS"])&&$_SERVER["HTTPS"]=="on"?'s':'').'://';
		$name = $_SERVER['SERVER_NAME'];
		$ssl = isset($_SERVER["HTTPS"])&&$_SERVER["HTTPS"]==="on";
		$port = isset($_SERVER['SERVER_PORT'])&&$_SERVER['SERVER_PORT']&&((!$ssl&&(int)$_SERVER['SERVER_PORT']!=80)||($ssl&&(int)$_SERVER['SERVER_PORT']!=443))?':'.$_SERVER['SERVER_PORT']:'';
		if(isset($_SERVER['SURIKAT_URI'])){
			$suffixHref = ltrim($_SERVER['SURIKAT_URI'],'/');
		}
		else{
			$docRoot = $_SERVER['DOCUMENT_ROOT'].'/';
			if(defined('SURIKAT_CWD'))
				$cwd = SURIKAT_CWD;
			else
				$cwd = getcwd();
			if($docRoot!=$cwd&&strpos($cwd,$docRoot)===0)
				$suffixHref = substr($cwd,strlen($docRoot));
		}
		return $protocol.$name.$port.'/'.$suffixHref;
	}

	function getRight(){
		if(!isset($this->right))
			$this->right = $this->Session->get('_AUTH_','right');
		return $this->right;
	}
	function setRight($r){
		$this->right = $r;
	}
	
	function connected(){
		return !!$this->Session->get('_AUTH_');
	}
	function allowed($d){
		if(is_string($d)) $d = constant(__CLASS__.'::'.$d);
		return !!($d&$this->getRight());
	}
	function allow($d){
		if(is_string($d)) $d = constant(__CLASS__.'::'.$d);
		return $this->setRight($d|$this->getRight());
	}
	function deny($d){
		if(is_string($d)) $d = constant(__CLASS__.'::'.$d);
		return $this->setRight($d^$this->getRight());
	}
	
	function lock($r,$redirect=true){
		if($this->allowed($r))
			return;
		
		//nocache headers
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" );
		header("Pragma: no-cache");
		header("Cache-Control: no-cache");
		header("Expires: -1");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Cache-Control: no-store, no-cache, must-revalidate");
		
		if($redirect){
			if($this->connected())
				$redirect = '401';
			if($redirect===true)
				$redirect = isset($this->siteLoginUri)?$this->siteLoginUri:'401';
			header('Location: '.$this->siteUrl.$redirect,false,302);
		}
		else{
			http_response_code(401);
		}
		exit;
	}
}