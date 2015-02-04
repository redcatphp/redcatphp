<?php namespace Surikat\Tool;
use Surikat\Core\Config;
use Surikat\Core\Session;
use Surikat\Core\FS;
use Surikat\Core\HTTP;
use Surikat\Model\R;
use Surikat\I18n\Lang;
use Surikat\Tool\AuthDigest;
use Core\Domain;
use Exception;
if (version_compare(phpversion(), '5.5.0', '<')){
	require_once SURIKAT_SPATH.'php/Tool/Crypto/password-compat.inc.php';
}
Lang::initialize();
class Auth{
	const ERROR_USER_BLOCKED = 1;
	const ERROR_USER_BLOCKED_2 = 46;
	const ERROR_USER_BLOCKED_3 = 47;
	const ERROR_NAME_SHORT = 2;
	const ERROR_NAME_LONG = 3;
	const ERROR_NAME_INCORRECT = 4;
	const ERROR_NAME_INVALID = 5;
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
	const ERROR_NAME_PASSWORD_INVALID = 16;
	const ERROR_NAME_PASSWORD_INCORRECT = 17;
	const ERROR_EMAIL_INVALID = 18;
	const ERROR_EMAIL_INCORRECT = 19;
	const ERROR_NEWEMAIL_MATCH = 20;
	const ERROR_ACCOUNT_INACTIVE = 21;
	const ERROR_SYSTEM_ERROR = 22;
	const ERROR_NAME_TAKEN = 23;
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
	

	const RIGHT_MANAGE = 2;
	const RIGHT_EDIT = 4;
	const RIGHT_MODERATE = 8;
	
	const ROLE_ADMIN = 14;
	const ROLE_EDITOR = 4;
	const ROLE_MODERATOR = 8;
	
	static $instances;
	private $db;
	protected $tableRequests = 'request';
	protected $tableUsers = 'user';
	protected $siteUrl;
	protected $siteName = '';
	protected $siteEmail = '';
	protected $siteLoginUri = 'Login';
	protected $siteActivateUri = 'Signin';
	protected $siteResetUri = 'Signin';
	protected $cost = 10;
	protected $algo;
	protected $messages;
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
	
	function sendMail($email, $type, $key){
		if($type == "activation"){
			$message = "Account activation required : <strong><a href=\"{$this->siteUrl}{$this->siteActivateUri}?action=activate&key={$key}\">Activate my account</a></strong>";
			$subject = "{$this->siteName} - Account Activation";
		}
		else{
			$message = "Password reset request : <strong><a href=\"{$this->siteUrl}{$this->siteResetUri}?action=resetpass&key={$key}\">Reset my password</a></strong>";
			$subject = "{$this->siteName} - Password reset request";
		}
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "From: {$this->siteEmail}" . "\r\n";
		return mail($email, $subject, $message, $headers);
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
		if(isset($this->config['siteName']))
			$this->siteName = $this->config['siteName'];
		if(isset($this->config['siteEmail']))
			$this->siteEmail = $this->config['siteEmail'];
		if(isset($this->config['siteLoginUri']))
			$this->siteLoginUri = $this->config['siteLoginUri'];
		if(isset($this->config['siteActivateUri']))
			$this->siteActivateUri = $this->config['siteActivateUri'];
		if(isset($this->config['siteResetUri']))
			$this->siteResetUri = $this->config['siteResetUri'];
		if(isset($this->config['tableUsers'])&&$this->config['tableUsers'])
			$this->tableUsers = $this->config['tableUsers'];
		if(isset($this->config['tableRequests'])&&$this->config['tableRequests'])
			$this->tableRequests = $this->config['tableRequests'];
		if(isset($this->config['algo'])&&$this->config['algo'])
			$this->algo = $this->config['algo'];
		else
			$this->algo = PASSWORD_DEFAULT;
	}
	
	public function getMessage($code,$widget=false){
		if(!isset($this->messages)){
			$this->messages = [
				self::ERROR_USER_BLOCKED => __("Too many failed attempts, try again in %d seconds",null,'auth'),
				self::ERROR_USER_BLOCKED_2 => __("Too many failed attempts, try again in %d minutes and %d seconds",null,'auth'),
				self::ERROR_USER_BLOCKED_3 => __("Too many failed attempts, try again in :",null,'auth'),
				self::ERROR_NAME_SHORT => __("Username is too short",null,'auth'),
				self::ERROR_NAME_LONG => __("Username is too long",null,'auth'),
				self::ERROR_NAME_INCORRECT => __("Username is incorrect",null,'auth'),
				self::ERROR_NAME_INVALID => __("Username is invalid",null,'auth'),
				self::ERROR_PASSWORD_SHORT => __("Password is too short",null,'auth'),
				self::ERROR_PASSWORD_LONG => __("Password is too long",null,'auth'),
				self::ERROR_PASSWORD_INVALID => __("Password must contain at least one uppercase and lowercase character, and at least one digit",null,'auth'),
				self::ERROR_PASSWORD_NOMATCH => __("Passwords do not match",null,'auth'),
				self::ERROR_PASSWORD_INCORRECT => __("Current password is incorrect",null,'auth'),
				self::ERROR_PASSWORD_NOTVALID => __("Password is invalid",null,'auth'),
				self::ERROR_NEWPASSWORD_SHORT => __("New password is too short",null,'auth'),
				self::ERROR_NEWPASSWORD_LONG => __("New password is too long",null,'auth'),
				self::ERROR_NEWPASSWORD_INVALID => __("New password must contain at least one uppercase and lowercase character, and at least one digit",null,'auth'),
				self::ERROR_NEWPASSWORD_NOMATCH => __("New passwords do not match",null,'auth'),
				self::ERROR_NAME_PASSWORD_INVALID => __("Username / Password are invalid",null,'auth'),
				self::ERROR_NAME_PASSWORD_INCORRECT => __("Username / Password are incorrect",null,'auth'),
				self::ERROR_EMAIL_INVALID => __("Email address is invalid",null,'auth'),
				self::ERROR_EMAIL_INCORRECT => __("Email address is incorrect",null,'auth'),
				self::ERROR_NEWEMAIL_MATCH => __("New email matches previous email",null,'auth'),
				self::ERROR_ACCOUNT_INACTIVE => __("Account has not yet been activated",null,'auth'),
				self::ERROR_SYSTEM_ERROR => __("A system error has been encountered. Please try again",null,'auth'),
				self::ERROR_NAME_TAKEN => __("The name is already taken",null,'auth'),
				self::ERROR_EMAIL_TAKEN => __("The email address is already in use",null,'auth'),
				self::ERROR_AUTHENTICATION_REQUIRED => __("Authentication required",null,'auth'),
				self::ERROR_ALREADY_AUTHENTICATED => __("You are already authenticated",null,'auth'),
				self::ERROR_RESETKEY_INVALID => __("Reset key is invalid",null,'auth'),
				self::ERROR_RESETKEY_INCORRECT => __("Reset key is incorrect",null,'auth'),
				self::ERROR_RESETKEY_EXPIRED => __("Reset key has expired",null,'auth'),
				self::ERROR_ACTIVEKEY_INVALID => __("Activation key is invalid",null,'auth'),
				self::ERROR_ACTIVEKEY_INCORRECT => __("Activation key is incorrect",null,'auth'),
				self::ERROR_ACTIVEKEY_EXPIRED => __("Activation key has expired",null,'auth'),
				self::ERROR_RESET_EXISTS => __("A reset request already exists",null,'auth'),
				self::ERROR_ALREADY_ACTIVATED => __("Account is already activated",null,'auth'),
				self::ERROR_ACTIVATION_EXISTS => __("An activation email has already been sent",null,'auth'),
				
				self::OK_PASSWORD_CHANGED => __("Password changed successfully",null,'auth'),
				self::OK_EMAIL_CHANGED => __("Email address changed successfully",null,'auth'),
				self::OK_ACCOUNT_ACTIVATED => __("Account has been activated. You can now log in",null,'auth'),
				self::OK_ACCOUNT_DELETED => __("Account has been deleted",null,'auth'),
				self::OK_LOGGED_IN => __("You are now logged in",null,'auth'),
				self::OK_LOGGED_OUT => __("You are now logged out",null,'auth'),
				self::OK_REGISTER_SUCCESS => __("Account created. Activation email sent to email",null,'auth'),
				self::OK_PASSWORD_RESET => __("Password reset successfully",null,'auth'),
				self::OK_RESET_REQUESTED => __("Password reset request sent to email address",null,'auth'),
				self::OK_ACTIVATION_SENT => __("Activation email has been sent",null,'auth'),
			];
		}
		if(is_array($code)){
			$c = array_shift($code);
			switch($c){
				case self::ERROR_USER_BLOCKED:
					$t = array_shift($code);
					if($t>60){
						$c = self::ERROR_USER_BLOCKED_2;
						$code[] = floor($t/60);
						$code[] = $t%60;
					}
					else{
						$code[] = $t;
					}
					if($widget){
						if($t>60){
							$minutes = floor($t/60);
							$t = $t%60;
						}
						else{
							$minutes = 0;
						}
						$r = '<div id="msgcountdown">'.$this->getMessage([self::ERROR_USER_BLOCKED,$t]).'</div>';
						$r .= '<div id="countdown"></div>';
						$r .= '<script>
							var interval;
							var minutes = '.$minutes.';
							var seconds = '.$t.';
							window.onload = function(){
								var countdown = document.getElementById("countdown");
								var msgcountdown = document.getElementById("msgcountdown");
								var showCountDown = function(){
									if(seconds == 0) {
										if(minutes == 0) {
											countdown.innerHTML = "";
											msgcountdown.innerHTML = "";
											clearInterval(interval);
											return;
										} else {
											minutes--;
											seconds = 60;
										}
									}
									if(minutes > 0) {
										var minute_text = minutes + (minutes > 1 ? "minutes" : "minute");
									} else {
										var minute_text = "";
									}
									var second_text = seconds > 1 ? "seconds" : "second";
									countdown.innerHTML = minute_text + " " + seconds + " " + second_text;
									seconds--;
								};
								msgcountdown.innerHTML = "'.$this->getMessage(self::ERROR_USER_BLOCKED_3).'";
								showCountDown();
								var interval = setInterval(showCountDown,1000);
							}
						</script>';
						return $r;
					}
				break;
			}
			array_unshift($code,$this->messages[$c]);
			return call_user_func_array('sprintf',$code);
		}
		else{
			return $this->messages[$code];
		}
	}
	public function loginRoot($password,$lifetime=0){
		$pass = $this->config['root'];
		if(!$pass)
			return self::ERROR_SYSTEM_ERROR;
		$id = 0;
		if(strpos($pass,'$')!==0){
			if($pass!=$password){
				Session::addAttempt();
				return self::ERROR_NAME_PASSWORD_INCORRECT;
			}
		}
		else{
			if(!password_verify($password, $pass)){
				Session::addAttempt();
				return self::ERROR_NAME_PASSWORD_INCORRECT;
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
			$id = $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE name = ?',[$this->superRoot]);
			if(!$id){
				$id = $this->db
					->newOne($this->tableUsers,['name'=>$this->superRoot])
					->store()
				;
				if(!$id){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		$this->addSession([
			'id'=>$id,
			'name'=>$this->superRoot,
			'email'=>isset($this->config['email'])?$this->config['email']:null,
			'right'=>static::ROLE_ADMIN,
		],$lifetime);
		return self::OK_LOGGED_IN;
	}
	public function login($name, $password, $lifetime=0){
		if($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if($name==$this->superRoot)
			return $this->loginRoot($password,$lifetime);
		try{
			$this->validateUsername($name);
			$this->validatePassword($password);
		}
		catch(Exception $e){
			Session::addAttempt();
			throw new Exception(self::ERROR_NAME_PASSWORD_INVALID);
		}
		$uid = $this->getUID($name);
		if(!$uid){
			Session::addAttempt();
			return self::ERROR_NAME_PASSWORD_INCORRECT;
		}
		$user = $this->getUser($uid);
		if(!password_verify($password, $user['password'])){
			Session::addAttempt();
			return self::ERROR_NAME_PASSWORD_INCORRECT;
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
			Session::addAttempt();
			return self::ERROR_ACCOUNT_INACTIVE;
		}
		if(!$this->addSession($user,$lifetime)){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_LOGGED_IN;
	}

	public function register($email, $name, $password, $repeatpassword){
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		$this->validateEmail($email);
		$this->validateUsername($name);
		$this->validatePassword($password);
		if($password!==$repeatpassword){
			return self::ERROR_PASSWORD_NOMATCH;
		}
		if($this->isEmailTaken($email)){
			Session::addAttempt();
			return self::ERROR_EMAIL_TAKEN;
		}
		if($this->isUsernameTaken($name)){
			Session::addAttempt();
			return self::ERROR_NAME_TAKEN;
		}
		$this->addUser($email, $name, $password);
		return self::OK_REGISTER_SUCCESS;
	}
	public function activate($key){
		if($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(strlen($key) !== 40){
			Session::addAttempt();
			return self::ERROR_ACTIVEKEY_INVALID;
		}
		$getRequest = $this->getRequest($key, "activation");
		$user = $this->getUser($getRequest[$this->tableUsers.'_id']);
		if(isset($user['active'])&&$user['active']==1){
			Session::addAttempt();
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
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		try{
			$this->validateEmail($email);
		}
		catch(Exception $e){
			throw new Exception(self::ERROR_EMAIL_INVALID);
		}
		$id = $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE email = ?',[$email]);
		if(!$id){
			Session::addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		try{
			$this->addRequest($id, $email, 'reset');
		}
		catch(Exception $e){
			Session::addAttempt();
			throw $e;
		}
		return self::OK_RESET_REQUESTED;
	}
	public function logout(){
		if(Session::destroy()){
			return self::OK_LOGGED_OUT;
		}
	}
	public function getHash($string, $salt){
		return password_hash($string, $this->algo, ['salt' => $salt, 'cost' => $this->cost]);
	}
	public function getUID($name){
		return $this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE name = ?',[$name]);
	}
	private function addSession($user,$lifetime=0){
		if(!Session::start())
			return false;
		Session::setCookieLifetime($lifetime);
		Session::setKey($user['id']);
		Session::set('_AUTH_',[
			'id'=>$user['id'],
			'email'=>$user['email'],
			'name'=>$user['name'],
			'right'=>$user['right'],
		]);
		return true;
	}
	private function isEmailTaken($email){
		return !!$this->db->getCell('SELECT id FROM '.$this->db->safeTable($this->tableUsers).' WHERE email = ?',[$email]);
	}
	private function isUsernameTaken($name){
		return !!$this->getUID($name);
	}
	private function addUser($email, $name, $password){
		$row = $this->db->create($this->tableUsers);
		if(!$row->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		$uid = $row->id;
		try{
			$this->addRequest($uid, $email, "activation");
		}
		catch(Exception $e){
			$row->trash();
			throw $e;
		}
		$salt = substr(strtr(base64_encode(\mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)), '+', '.'), 0, 22);
		$password = $this->getHash($password, $salt);
		$row->name = $name;
		$row->password = $password;
		$row->email = $email;
		$row->salt = $salt;
		if(!$row->store()){
			$row->trash();
			return self::ERROR_SYSTEM_ERROR;
		}
	}

	public function getUser($uid){
		return $this->db->load($this->tableUsers,(int)$uid);
	}

	public function deleteUser($uid, $password){
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		try{
			$this->validatePassword($password);
		}
		catch(Exception $e){
			Session::addAttempt();
			throw $e;
		}
		$getUser = $this->getUser($uid);
		if(!password_verify($password, $getUser['password'])){
			Session::addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row->trash()){
			return self::ERROR_SYSTEM_ERROR;
		}
		Session::destroyKey($uid);
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
		if(!$row){
			$expiredate = strtotime($row['expire']);
			$currentdate = strtotime(date("Y-m-d H:i:s"));
			if($currentdate < $expiredate){ //allready-exists
				return;
			}
			$this->deleteRequest($row['id']);
		}
		$user = $this->getUser($uid);
		if($type == "activation" && isset($user['active']) && $user['active'] == 1){
			return self::ERROR_ALREADY_ACTIVATED;
		}
		$key = $this->getRandomKey(40);
		$expire = date("Y-m-d H:i:s", strtotime("+1 day"));
		$user['xown'.ucfirst($this->tableRequests)][] = $this->db->create($this->tableRequests,['rkey'=>$key, 'expire'=>$expire, 'type'=>$type]);
		if(!$user->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!$this->sendMail($email, $type, $key)){
			return self::ERROR_SYSTEM_ERROR;
		}
	}
	private function getRequest($key, $type){
		$row = $this->db->findOne($this->tableRequests,' WHERE rkey = ? AND type = ?',[$key, $type]);
		if(!$row){
			Session::addAttempt();
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_INCORRECT;
			elseif($type=='reset')
				return self::ERROR_RESETKEY_INCORRECT;
			return;
		}
		$expiredate = strtotime($row['expire']);
		$currentdate = strtotime(date("Y-m-d H:i:s"));
		if ($currentdate > $expiredate){
			Session::addAttempt();
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
	public function validateUsername($name){
		if (strlen($name) < 1)
			return self::ERROR_NAME_SHORT;
		elseif (strlen($name) > 30)
			return self::ERROR_NAME_LONG;
		elseif (!ctype_alnum($name))
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
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		if(strlen($key) != 40){
			return self::ERROR_RESETKEY_INVALID;
		}
		$this->validatePassword($password);
		if($password !== $repeatpassword){ // Passwords don't match
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$data = $this->getRequest($key, "reset");
		$user = $this->getUser($data[$this->tableUsers.'_id']);
		if(!$user){
			Session::addAttempt();
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
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		$this->validateEmail($email);
		$row = $this->db->findOne($this->tableUsers,' WHERE email = ?',[$email]);
		if(!$row){
			Session::addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if(isset($row['active'])&&$row['active'] == 1){
			Session::addAttempt();
			return self::ERROR_ALREADY_ACTIVATED;
		}
		try{
			$this->addRequest($row['id'], $email, "activation");
		}
		catch(Exception $e){
			Session::addAttempt();
			throw $e;
		}
		return self::OK_ACTIVATION_SENT;
	}
	public function changePassword($uid, $currpass, $newpass, $repeatnewpass){
		if ($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		try{
			$this->validatePassword($currpass);
		}
		catch(Exception $e){
			Session::addAttempt();
			throw $e;
		}
		$this->validatePassword($newpass);
		if($newpass !== $repeatnewpass){
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$user = $this->getUser($uid);
		if(!$user){
			Session::addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		$newpass = $this->getHash($newpass, $user['salt']);
		if(!password_verify($currpass, $user['password'])){
			Session::addAttempt();
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
		if($s=Session::isBlocked()){
			return [self::ERROR_USER_BLOCKED,$s];
		}
		$this->validateEmail($email);
		try{
			$this->validatePassword($password);
		}
		catch(Exception $e){
			throw new Exception(self::ERROR_PASSWORD_NOTVALID);
		}
		$user = $this->getUser($uid);
		if(!$user){
			Session::addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!password_verify($password, $user['password'])){
			Session::addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if ($email == $user['email']){
			Session::addAttempt();
			return self::ERROR_NEWEMAIL_MATCH;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		$row->email = $email;
		if(!$row->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_EMAIL_CHANGED;
	}
	public function getRandomKey($length = 40){
		$chars = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6";
		$key = "";
		for ($i = 0; $i < $length; $i++)
			$key .= $chars{mt_rand(0, strlen($chars) - 1)};
		return $key;
	}
	
	private $right;
	function getRight(){
		if(!isset($this->right))
			$this->right = Session::get('_AUTH_','right');
		return $this->right;
	}
	function setRight($r){
		$this->right = $r;
	}
	
	function isConnected(){
		if(Session::start())
			return !!Session::get('_AUTH_');
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
				$redirect = $this->siteLoginUri;
			header('Location: '.$this->siteUrl.$redirect,false,302);
		}
		else{
			HTTP::code(403);
		}
		exit;
	}
	function _lockServer($r,$redirect=true){
		$action = Domain::getBaseHref().ltrim($_SERVER['REQUEST_URI'],'/').(isset($_SERVER['QUERY_STRING'])&&$_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:'');
		if(isset($_POST['__name__'])&&isset($_POST['__password__'])){
			if($this->login($_POST['__name__'],$_POST['__password__'])===self::OK_LOGGED_IN){
				header('Location: '.$action,false,302);
				exit;
			}
		}
		if($this->isAllowed($r))
			return;
		if($this->isConnected()){
			if($redirect)
				header('Location: '.$this->siteUrl.'403',false,302);
			else
				HTTP::code(403);
			exit;
		}
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Authentication</title>
		<style type="text/css">
			form{
				width:350px;
				margin:0 auto;
				display:block;
			}
			label,input{
				position:relative;
				float:left;
			}
			label{
				width:100px;
				font-size:1em;
				display:block;
			}
			input[type="submit"]{
				left:100px;
			}
		</style>
		</head><body>';
		if($seconds=Session::isBlocked()){
			echo $this->getMessage([self::ERROR_USER_BLOCKED,$seconds],true);
		}
		echo '<form id="form" action="'.$action.'" method="POST">
			<label for="__name__">Login</label><input type="text" id="__name__" name="__name__" placeholder="Login"><br>
			<label id="password" for="__password__">Password</label><input type="password" id="__password__" name="__password__" placeholder="Password"><br>
			<input id="submit" value="Connection" type="submit">
		</form>
		</body></html>';
		exit;
	}
}