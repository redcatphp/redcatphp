<?php namespace Surikat\User;
use Surikat\Config\Config;
use Surikat\FileSystem\FS;
use Surikat\HTTP\HTTP;
use Surikat\Model\R;
use Surikat\Mail\PHPMailer;
use Surikat\DependencyInjection\MutatorMagic;
use HTTP\Domain;
use Exception;
if (version_compare(phpversion(), '5.5.0', '<')){
	require_once SURIKAT_SPATH.'php/Crypto/password-compat.inc.php';
}
class Auth{
	use MutatorMagic;
	
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
	
	static $instances;
	private $db;
	protected $tableRequests = 'request';
	protected $tableUsers = 'user';
	protected $siteUrl;
	protected $cost = 10;
	protected $algo;
	protected $superRoot = 'root';
	protected $config = [];
		
	static function connected(){
		return self::instance()->isConnected();
	}
	static function allowed($right){
		return self::instance()->isAllowed($right);
	}
	static function lock($right,$redirect=true){
		return self::instance()->_lock($right,$redirect);
	}
	static function lockServer($right){
		return self::instance()->_lockServer($right);
	}
	
	static function instance($k=0){
		if(!isset(self::$instances[$k]))
			self::$instances[$k] = new static();
		return self::$instances[$k];
	}
	
	function sendMail($email, $type, $key, $login){
		$config = Config::mailer();
				
		$fromName = isset($config['fromName'])?$config['fromName']:null;
		$fromEmail = isset($config['fromEmail'])?$config['fromEmail']:null;
		$replyName = isset($config['replyName'])?$config['replyName']:null;
		$replyEmail = isset($config['replyEmail'])?$config['replyEmail']:null;
		$siteLoginUri = isset($this->config['siteLoginUri'])?$this->config['siteLoginUri']:null;
		$siteActivateUri = isset($this->config['siteActivateUri'])?$this->config['siteActivateUri']:null;
		$siteResetUri = isset($this->config['siteResetUri'])?$this->config['siteResetUri']:null;
		
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
	public function __construct(){
		$this->config = Config::auth();
		$dbm = 'db';
		if(isset($this->config['db'])&&$this->config['db'])
			$dbm .= '_'.$this->config['db'];
		$db = Config::$dbm();
		if((isset($db['name'])&&$db['name'])||(isset($db['file'])&&$db['file'])){
			$this->db = R::getDatabase(isset($this->config['db'])?$this->config['db']:null);
		}
		if(isset($this->config['siteUrl'])&&$this->config['siteUrl'])
			$this->siteUrl = $this->config['siteUrl'];
		else
			$this->siteUrl = Domain::getBaseHref();
		$this->siteUrl = rtrim($this->siteUrl,'/').'/';
		if(isset($this->config['tableUsers'])&&$this->config['tableUsers'])
			$this->tableUsers = $this->config['tableUsers'];
		if(isset($this->config['tableRequests'])&&$this->config['tableRequests'])
			$this->tableRequests = $this->config['tableRequests'];
		if(isset($this->config['algo'])&&$this->config['algo'])
			$this->algo = $this->config['algo'];
		else
			$this->algo = PASSWORD_DEFAULT;
	}
	public function loginRoot($password,$lifetime=0){
		$pass = $this->config['root'];
		if(!$pass)
			return self::ERROR_SYSTEM_ERROR;
		$id = 0;
		if(strpos($pass,'$')!==0){
			if($pass!=$password){
				$this->User_Session->addAttempt();
				return self::ERROR_LOGIN_PASSWORD_INCORRECT;
			}
		}
		else{
			if(!password_verify($password, $pass)){
				$this->User_Session->addAttempt();
				return self::ERROR_LOGIN_PASSWORD_INCORRECT;
			}
			else{
				$options = ['cost' => $this->cost];
				if(password_needs_rehash($pass, $this->algo, $options)){
					$this->config['root'] = password_hash($password, $this->algo, $options);
					if(!Config::STORE('auth',$this->config)){
						return self::ERROR_SYSTEM_ERROR;
					}
				}
			}
		}
		if($this->db){
			$id = $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE login = ?',[$this->superRoot]);
			if(!$id){
				$id = $this->db
					->newOne($this->tableUsers,[
						'login'=>$this->superRoot,
						'name'=>isset($this->config['rootName'])?$this->config['rootName']:$this->superRoot,
						'email'=>isset($this->config['rootEmail'])?$this->config['rootEmail']:null,
						'active'=>1,
						'right'=>static::ROLE_ADMIN,
						'type'=>'root'
					])
					->store()
				;
				if(!$id){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		$this->addSession([
			'id'=>$id,
			'login'=>$this->superRoot,
			'name'=>isset($this->config['rootName'])?$this->config['rootName']:$this->superRoot,
			'email'=>isset($this->config['email'])?$this->config['email']:null,
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
			'right'=>static::ROLE_MEMBER,
			'active'=>1,
		];
		if($this->db){
			$user = $this->db->findOne($this->tableUsers,' WHERE email = ? AND type = ?',[$email,'persona']);
			if(!$user){
				$user = $this->db->newOne($this->tableUsers,$userDefault);
				if(!$user->store()){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		else{
			$user = $userDefault;
			$user['id'] = $email;
		}
		$this->addSession($user,$lifetime);
		return self::OK_LOGGED_IN;
	}
	public function login($login, $password, $lifetime=0){
		if($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($login==$this->superRoot)
			return $this->loginRoot($password,$lifetime);
		if(!ctype_alnum($login)&&filter_var($login,FILTER_VALIDATE_EMAIL)){
			$login = $this->db->getCell('SELECT login FROM '.$this->db->safeTable($this->tableUsers).' WHERE email = ?',[$login]);
		}
		if($e=($this->validateLogin($login)||$this->validatePassword($password))){
			$this->User_Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INVALID;
		}
		$uid = $this->getUID($login);
		if(!$uid){
			$this->User_Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INCORRECT;
		}
		$user = $this->getUser($uid);
		if(!password_verify($password, $user['password'])){
			$this->User_Session->addAttempt();
			return self::ERROR_LOGIN_PASSWORD_INCORRECT;
		}
		else{
			$options = ['salt' => $user['salt'], 'cost' => $this->cost];
			if(password_needs_rehash($user['password'], $this->algo, $options)){
				$password = password_hash($password, $this->algo, $options);
				$row = $this->db->load($this->tableUsers,(int)$user['id']);
				$row->password = $password;
				if(!$row->store()){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		if(!isset($user['active'])||$user['active']!=1){
			$this->User_Session->addAttempt();
			return self::ERROR_ACCOUNT_INACTIVE;
		}
		if(!$this->addSession($user,$lifetime)){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_LOGGED_IN;
	}

	public function register($email, $login, $password, $repeatpassword, $name=null){
		if ($s=$this->User_Session->isBlocked()){
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
			$this->User_Session->addAttempt();
			return self::ERROR_EMAIL_TAKEN;
		}
		if($this->isLoginTaken($login)){
			$this->User_Session->addAttempt();
			return self::ERROR_LOGIN_TAKEN;
		}
		$this->addUser($email, $password, $login, $name);
		return self::OK_REGISTER_SUCCESS;
	}
	public function activate($key){
		if($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(strlen($key) !== 40){
			$this->User_Session->addAttempt();
			return self::ERROR_ACTIVEKEY_INVALID;
		}
		$getRequest = $this->getRequest($key, "activation");
		$user = $this->getUser($getRequest[$this->tableUsers.'_id']);
		if(isset($user['active'])&&$user['active']==1){
			$this->User_Session->addAttempt();
			$this->deleteRequest($getRequest['id']);
			return self::ERROR_SYSTEM_ERROR;
		}
		$row = $this->db->load($this->tableUsers,(int)$getRequest[$this->tableUsers.'_id']);
		$row->active = 1;
		$row->store();
		$this->deleteRequest($getRequest['id']);
		return self::OK_ACCOUNT_ACTIVATED;
	}
	public function requestReset($email){
		if ($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $e;
		$id = $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE email = ?',[$email]);
		if(!$id){
			$this->User_Session->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if($e=$this->addRequest($id, $email, 'reset')){
			$this->User_Session->addAttempt();
			return $e;
		}
		return self::OK_RESET_REQUESTED;
	}
	public function logout(){
		if($this->isConnected()&&$this->User_Session->destroy()){
			return self::OK_LOGGED_OUT;
		}
		return $this->User_Session->destroy();
	}
	public function getHash($string, $salt){
		return password_hash($string, $this->algo, ['salt' => $salt, 'cost' => $this->cost]);
	}
	public function getUID($login){
		return $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE login = ?',[$login]);
	}
	private function addSession($user,$lifetime=0){
		$this->User_Session->setCookieLifetime($lifetime);
		$this->User_Session->setKey($user['id']);
		$this->User_Session->set('_AUTH_',[
			'id'=>$user['id'],
			'email'=>$user['email'],
			'login'=>$user['login'],
			'name'=>$user['name'],
			'right'=>$user['right'],
			'type'=>$user['type'],
		]);
		return true;
	}
	private function isEmailTaken($email){
		return !!$this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE email = ?',[$email]);
	}
	private function isLoginTaken($login){
		return !!$this->getUID($login);
	}
	private function addUser($email, $password, $login=null, $name=null){
		$row = $this->db->create($this->tableUsers);
		if(!$row->store()){
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
		$row->right = static::ROLE_MEMBER;
		$row->type = 'local';
		if(!$row->store()){
			$row->trash();
			return self::ERROR_SYSTEM_ERROR;
		}
	}

	public function getUser($uid){
		return $this->db->load($this->tableUsers,(int)$uid);
	}

	public function deleteUser($uid, $password){
		if ($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validatePassword($password)){
			$this->User_Session->addAttempt();
			return $e;
		}
		$getUser = $this->getUser($uid);
		if(!password_verify($password, $getUser['password'])){
			$this->User_Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row->trash()){
			return self::ERROR_SYSTEM_ERROR;
		}
		$this->User_Session->destroyKey($uid);
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
			$this->deleteRequest($row['id']);
		}
		$user = $this->getUser($uid);
		if($type == "activation" && isset($user['active']) && $user['active'] == 1){
			return self::ERROR_ALREADY_ACTIVATED;
		}
		$key = self::getRandomKey(40);
		$expire = date("Y-m-d H:i:s", strtotime("+1 day"));
		$user['xown'.ucfirst($this->tableRequests)][] = $this->db->create($this->tableRequests,['rkey'=>$key, 'expire'=>$expire, 'type'=>$type]);
		if(!$user->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!$this->sendMail($email, $type, $key, $user['name'])){
			return self::ERROR_SYSTEM_ERROR;
		}
	}
	private function getRequest($key, $type){
		$row = $this->db->findOne($this->tableRequests,' WHERE rkey = ? AND type = ?',[$key, $type]);
		if(!$row){
			$this->User_Session->addAttempt();
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_INCORRECT;
			elseif($type=='reset')
				return self::ERROR_RESETKEY_INCORRECT;
			return;
		}
		$expiredate = strtotime($row['expire']);
		$currentdate = strtotime(date("Y-m-d H:i:s"));
		if ($currentdate > $expiredate){
			$this->User_Session->addAttempt();
			$this->deleteRequest($row['id']);
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_EXPIRED;
			elseif($type=='reset')
				return self::ERROR_ACTIVEKEY_EXPIRED;
		}
		return [
			'id' => $row['id'],
			$this->tableUsers.'_id' => $row[$this->tableUsers]['id']
		];
	}
	private function deleteRequest($id){
		return $this->db->exec('DELETE FROM '.$this->db->safeTable($this->tableRequests).' WHERE id = ?',[$id]);
	}
	public function validateLogin($login){
		if (strlen($login) < 1)
			return self::ERROR_LOGIN_SHORT;
		elseif (strlen($login) > 30)
			return self::ERROR_LOGIN_LONG;
		elseif (!ctype_alnum($login))
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
		if ($s=$this->User_Session->isBlocked()){
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
			$this->User_Session->addAttempt();
			$this->deleteRequest($data['id']);
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!password_verify($password, $user['password'])){			
			$password = $this->getHash($password, $user['salt']);
			$row = $this->db->load($this->tableUsers,$data[$this->tableUsers.'_id']);
			$row->password = $password;
			if (!$row->store()){
				return self::ERROR_SYSTEM_ERROR;
			}
		}
		$this->deleteRequest($data['id']);
		return self::OK_PASSWORD_RESET;
	}
	public function resendActivation($email){
		if ($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $r;
		$row = $this->db->findOne($this->tableUsers,' WHERE email = ?',[$email]);
		var_dump($row);
		if(!$row){
			$this->User_Session->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if(isset($row['active'])&&$row['active'] == 1){
			$this->User_Session->addAttempt();
			return self::ERROR_ALREADY_ACTIVATED;
		}
		if($e=$this->addRequest($row['id'], $email, "activation")){
			$this->User_Session->addAttempt();
			return $e;
		}
		return self::OK_ACTIVATION_SENT;
	}
	public function changePassword($uid, $currpass, $newpass, $repeatnewpass){
		if ($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validatePassword($currpass)){
			$this->User_Session->addAttempt();
			return $e;
		}
		if($e=$this->validatePassword($newpass))
			return $e;
		if($newpass !== $repeatnewpass){
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$user = $this->getUser($uid);
		if(!$user){
			$this->User_Session->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		$newpass = $this->getHash($newpass, $user['salt']);
		if(!password_verify($currpass, $user['password'])){
			$this->User_Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if($currpass != $newpass){			
			$row = $this->db->load($this->tableUsers,(int)$uid);
			$row->password = $newpass;
			$row->store();
		}
		return self::OK_PASSWORD_CHANGED;
	}
	public function getEmail($uid){
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if (!$row->id){
			return false;
		}
		return $row['email'];
	}
	public function changeEmail($uid, $email, $password){
		if($s=$this->User_Session->isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($e=$this->validateEmail($email))
			return $e;
		if($e=$this->validatePassword($password))
			return $e;
		$user = $this->getUser($uid);
		if(!$user){
			$this->User_Session->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!password_verify($password, $user['password'])){
			$this->User_Session->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if ($email == $user['email']){
			$this->User_Session->addAttempt();
			return self::ERROR_NEWEMAIL_MATCH;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		$row->email = $email;
		if(!$row->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_EMAIL_CHANGED;
	}
	static function getRandomKey($length = 40){
		$chars = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6";
		$key = "";
		for ($i = 0; $i < $length; $i++)
			$key .= $chars{mt_rand(0, strlen($chars) - 1)};
		return $key;
	}
	
	private $right;
	function getRight(){
		if(!isset($this->right))
			$this->right = $this->User_Session->get('_AUTH_','right');
		return $this->right;
	}
	function setRight($r){
		$this->right = $r;
	}
	
	function isConnected(){
		return !!$this->User_Session->get('_AUTH_');
	}
	function isAllowed($d){
		return !!($d&$this->getRight());
	}
	function allow($d){
		return $this->setRight($d|$this->getRight());
	}
	function deny($d){
		return $this->setRight($d^$this->getRight());
	}
	
	function _lock($r,$redirect=true){
		if($this->isAllowed($r))
			return;
		HTTP::nocacheHeaders();
		if($redirect){
			if($this->isConnected())
				$redirect = '403';
			if($redirect===true)
				$redirect = isset($this->config['siteLoginUri'])?$this->config['siteLoginUri']:'403';
			header('Location: '.$this->siteUrl.$redirect,false,302);
		}
		else{
			HTTP::code(403);
		}
		exit;
	}
	function _lockServer($r,$redirect=true){
		return (new AuthServer($this))->htmlLock($r,$redirect);
	}
}