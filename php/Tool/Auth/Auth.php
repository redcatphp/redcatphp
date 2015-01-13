<?php namespace Surikat\Tool\Auth;
/*
	API
	
	Auth::RIGHT_ADMIN
	Auth::lock($right)		COOKIE OR 403
	Auth::lockHTTP($right)	COOKIE OR CHECK-HTTP OR 401
	
	$auth->register($email, $name, $password, $repeatpassword)
	$auth->activate($key)
	$auth->resendActivation($email)
	$auth->login($name, $password)
	$auth->requestReset($email)
	$auth->resetPass($key, $password, $repeatpassword)
	$auth->changePassword($uid, $currpass, $newpass, $repeatnewpass)
	$auth->changeEmail($uid, $email, $password)
	$auth->deleteUser($uid, $password)
	$auth->logout()
*/
use Surikat\Core\Config;
use Surikat\Core\Session;
use Surikat\Core\FS;
use Surikat\Model\R;
use Surikat\I18n\Lang;
use Core\Domain;
use Exception;

if (version_compare(phpversion(), '5.5.0', '<')) {
	require_once SURIKAT_SPATH.'php/Tool/Crypto/password-compat.inc.php';
}
Lang::initialize();
class Auth{
	const ERROR_USER_BLOCKED = 1;
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
	
	static $One;
	private $db;
	protected $tableRequests = 'requests';
	protected $tableUsers = 'users';
	protected $site_name = 'The Lab';
	protected $site_url;
	protected $site_email = 'no-reply@lab.cuonic.com';
	protected $bcrypt_cost = '10';
	protected $messages;
	protected $attemptsPath;
		
	static function lock(){
		
	}
	static function lockHTTP(){
		
	}
	static function One(){
		if(!isset(self::$One))
			self::$One = new static();
		return self::$One;
	}
	
	public function __construct(){
		$this->db = R::getDatabase();
		$this->site_url = rtrim(Domain::getBaseHref(),'/');
		$this->attemptsPath = SURIKAT_PATH.'.tmp/attempts/';
	}
	
	public function getMessage($code){
		if(!isset($this->messages)){
			$this->messages = [
				self::ERROR_USER_BLOCKED => __("You are currently locked out of the system",null,'auth'),
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
		return $this->messages[$code];
	}
	public function login($name, $password){
		if($this->isBlocked()){
			return self::ERROR_USER_BLOCKED;
		}
		try{			
			$this->validateUsername($name);
			$this->validatePassword($password);
		}
		catch(Exception $e){
			$this->addAttempt();
			throw new Exception(self::ERROR_NAME_PASSWORD_INVALID);
		}
		$uid = $this->getUID(strtolower($name));
		if(!$uid){
			$this->addAttempt();
			return self::ERROR_NAME_PASSWORD_INCORRECT;
		}
		$user = $this->getUser($uid);
		if(!password_verify($password, $user['password'])){
			$this->addAttempt();
			return self::ERROR_NAME_PASSWORD_INCORRECT;
		}
		else{
			$options = ['salt' => $user['salt'], 'cost' => $this->bcrypt_cost];
			if(password_needs_rehash($user['password'], PASSWORD_BCRYPT, $options)){
				$password = password_hash($password, PASSWORD_BCRYPT, $options);
				$row = $this->db->load($this->tableUsers,(int)$user['id']);
				$row->password = $password;
				if(!$row->store()){
					return self::ERROR_SYSTEM_ERROR;
				}
			}
		}
		if(!isset($user['active'])||$user['active']!=1){
			$this->addAttempt();
			return self::ERROR_ACCOUNT_INACTIVE;
		}
		$sessiondata = $this->addSession($user['id']);
		if($sessiondata==false){
			return self::ERROR_SYSTEM_ERROR;

		}
		Session::setKey($user['id']);
		return self::OK_LOGGED_IN;
	}

	public function register($email, $name, $password, $repeatpassword){
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		$this->validateEmail($email);
		$this->validateUsername($name);
		$this->validatePassword($password);
		if($password!==$repeatpassword){
			return self::ERROR_PASSWORD_NOMATCH;
		}
		if($this->isEmailTaken($email)){
			$this->addAttempt();
			return self::ERROR_EMAIL_TAKEN;
		}
		if($this->isUsernameTaken($name)){
			$this->addAttempt();
			return self::ERROR_NAME_TAKEN;
		}
		$this->addUser($email, $name, $password);
		return self::OK_REGISTER_SUCCESS;
	}
	public function activate($key){
		if($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		if(strlen($key) !== 40) {
			$this->addAttempt();
			return self::ERROR_ACTIVEKEY_INVALID;
		}
		$getRequest = $this->getRequest($key, "activation");
		$user = $this->getUser($getRequest[$this->tableUsers.'_id']);
		if(isset($user['active'])&&$user['active']==1) {
			$this->addAttempt();
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
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		try{
			$this->validateEmail($email);
		}
		catch(Exception $e){
			throw new Exception(self::ERROR_EMAIL_INVALID);
		}
		$id = $this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE email = ?",[$email]);
		if(!$id){
			$this->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		try{
			$this->addRequest($id, $email, 'reset');
		}
		catch(Exception $e){
			$this->addAttempt();
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
		return password_hash($string, PASSWORD_BCRYPT, ['salt' => $salt, 'cost' => $this->bcrypt_cost]);
	}
	public function getUID($name){
		return $this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE name = ?",[$name]);
	}
	private function addSession($uid){
		$ip = $this->getIp();
		$user = $this->getUser($uid);
		if(!$user) {
			return false;
		}
		Session::destroyKey($uid);
		if(!Session::start()){
			return false;
		}
		$data = [
			'id'=>$uid,
			'email'=>$user['email'],
			'name'=>$user['name'],
		];
		Session::set('_AUTH_',$data);
		return $data;
	}
	private function isEmailTaken($email){
		return !!$this->db->getCell("SELECT id FROM {$this->tableUsers} WHERE email = ?",[$email]);
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
		$email = htmlentities($email);
		try{
			$this->addRequest($uid, $email, "activation");
		}
		catch(Exception $e){
			$row->trash();
			throw $e;
		}
		$salt = substr(strtr(base64_encode(\mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)), '+', '.'), 0, 22);
		$name = htmlentities(strtolower($name));
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
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row)
			return false;
		return $row->getProperties();
	}

	public function deleteUser($uid, $password) {
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		try{
			$this->validatePassword($password);
		}
		catch(Exception $e){
			$this->addAttempt();
			throw $e;
		}
		$getUser = $this->getUser($uid);
		if(!password_verify($password, $getUser['password'])) {
			$this->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		if(!$row->trash()){
			return self::ERROR_SYSTEM_ERROR;
		}
		Session::destroyKey($uid);
		foreach($row->own($this->tableRequests) as $request){
			if(!$request->trash()) {
				return self::ERROR_SYSTEM_ERROR;
			}
		}		
		return self::OK_ACCOUNT_DELETED;
	}
	private function addRequest($uid, $email, $type){
		if($type != "activation" && $type != "reset") {
			return self::ERROR_SYSTEM_ERROR;
		}
		$row = $this->db->findOne($this->tableRequests," WHERE {$this->tableUsers}_id = ? AND type = ?",[$uid, $type]);
		if(!$row){
			$expiredate = strtotime($row['expire']);
			$currentdate = strtotime(date("Y-m-d H:i:s"));
			if($currentdate < $expiredate){ //allready-exists
				return;
			}
			$this->deleteRequest($row['id']);
		}
		$user = $this->getUser($uid);
		if($type == "activation" && isset($user['active']) && $user['active'] == 1) {
			return self::ERROR_ALREADY_ACTIVATED;
		}
		$key = $this->getRandomKey(40);
		$expire = date("Y-m-d H:i:s", strtotime("+1 day"));
		$request = $this->db->create($this->tableRequests,[$this->tableUsers.'_id'=>$uid, 'rkey'=>$key, 'expire'=>$expire, 'type'=>$type]);
		if(!$request->store()) {
			return self::ERROR_SYSTEM_ERROR;
		}
		if($type == "activation") {
			$message = "Account activation required : <strong><a href=\"{$this->site_url}/activate/{$key}\">Activate my account</a></strong>";
			$subject = "{$this->site_name} - Account Activation";
		} else {
			$message = "Password reset request : <strong><a href=\"{$this->site_url}/reset/{$key}\">Reset my password</a></strong>";		
			$subject = "{$this->site_name} - Password reset request";
		}
		//$headers  = 'MIME-Version: 1.0' . "\r\n";
		//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		//$headers .= "From: {$this->site_email}" . "\r\n";
		//if(!mail($email, $subject, $message, $headers)) {
			//return self::ERROR_SYSTEM_ERROR;
		//}
	}
	private function getRequest($key, $type){
		$row = $this->db->findOne($this->tableRequests,' WHERE rkey = ? AND type = ?',[$key, $type]);
		if(!$row) {
			$this->addAttempt();
			if($type=='activation')
				return self::ERROR_ACTIVEKEY_INCORRECT;
			elseif($type=='reset')
				return self::ERROR_RESETKEY_INCORRECT;
			return;
		}
		$expiredate = strtotime($row['expire']);
		$currentdate = strtotime(date("Y-m-d H:i:s"));
		if ($currentdate > $expiredate) {
			$this->addAttempt();
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
		return $this->db->exec("DELETE FROM {$this->tableRequests} WHERE id = ?",[$id]);
	}
	public function validateUsername($name) {
		if (strlen($name) < 3)
			return self::ERROR_NAME_SHORT;
		elseif (strlen($name) > 30)
			return self::ERROR_NAME_LONG;
		elseif (!ctype_alnum($name))
			return self::ERROR_NAME_INVALID;
	}
	private function validatePassword($password) {
		if (strlen($password) < 6)
			return self::ERROR_PASSWORD_SHORT;
		elseif (strlen($password) > 72)
			return self::ERROR_PASSWORD_LONG;
		elseif ((!preg_match('@[A-Z]@', $password) && !preg_match('@[a-z]@', $password)) || !preg_match('@[0-9]@', $password))
			return self::ERROR_PASSWORD_INVALID;
	}
	private function validateEmail($email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return self::ERROR_EMAIL_INVALID;
	}
	public function resetPass($key, $password, $repeatpassword){
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		if(strlen($key) != 40) {
			return self::ERROR_RESETKEY_INVALID;
		}
		$this->validatePassword($password);
		if($password !== $repeatpassword) { // Passwords don't match
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$data = $this->getRequest($key, "reset");
		$user = $this->getUser($data[$this->tableUsers.'_id']);
		if(!$user) {
			$this->addAttempt();
			$this->deleteRequest($data['id']);
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!password_verify($password, $user['password'])) {			
			$password = $this->getHash($password, $user['salt']);
			$row = $this->db->load($this->tableUsers,$data[$this->tableUsers.'_id']);
			$row->password = $password;
			if (!$row->store()) {
				return self::ERROR_SYSTEM_ERROR;
			}
		}
		$this->deleteRequest($data['id']);
		return self::OK_PASSWORD_RESET;
	}
	public function resendActivation($email){
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		$this->validateEmail($email);
		$row = $this->db->findOne($this->tableUsers,' WHERE email = ?',[$email]);
		if(!$row){
			$this->addAttempt();
			return self::ERROR_EMAIL_INCORRECT;
		}
		if(isset($row['active'])&&$row['active'] == 1){
			$this->addAttempt();
			return self::ERROR_ALREADY_ACTIVATED;
		}
		try{
			$this->addRequest($row['id'], $email, "activation");
		}
		catch(Exception $e){
			$this->addAttempt();
			throw $e;
		}
		return self::OK_ACTIVATION_SENT;
	}
	public function changePassword($uid, $currpass, $newpass, $repeatnewpass){
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		try{
			$this->validatePassword($currpass);
		}
		catch(Exception $e){
			$this->addAttempt();
			throw $e;
		}
		$this->validatePassword($newpass);
		if($newpass !== $repeatnewpass) {
			return self::ERROR_NEWPASSWORD_NOMATCH;
		}
		$user = $this->getUser($uid);
		if(!$user) {
			$this->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		$newpass = $this->getHash($newpass, $user['salt']);
		if(!password_verify($currpass, $user['password'])) {
			$this->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if($currpass != $newpass) {			
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
		if ($this->isBlocked()) {
			return self::ERROR_USER_BLOCKED;
		}
		$this->validateEmail($email);
		try{
			$this->validatePassword($password);
		}
		catch(Exception $e){
			throw new Exception(self::ERROR_PASSWORD_NOTVALID);
		}
		$user = $this->getUser($uid);
		if(!$user) {
			$this->addAttempt();
			return self::ERROR_SYSTEM_ERROR;
		}
		if(!password_verify($password, $user['password'])) {
			$this->addAttempt();
			return self::ERROR_PASSWORD_INCORRECT;
		}
		if ($email == $user['email']) {
			$this->addAttempt();
			return self::ERROR_NEWEMAIL_MATCH;
		}
		$row = $this->db->load($this->tableUsers,(int)$uid);
		$row->email = $email;
		if(!$row->store()){
			return self::ERROR_SYSTEM_ERROR;
		}
		return self::OK_EMAIL_CHANGED;
	}
	private function isBlocked(){
		$ip = $this->getIp();
		if(is_file($this->attemptsPath.$ip))
			$count = (int)file_get_contents($this->attemptsPath.$ip);
		else
			return false;
		$expiredate = filemtime($this->attemptsPath.$ip)+1800;
		$currentdate = time();
		if($count==5){
			if($currentdate<$expiredate)
				return true;
			$this->deleteAttempts();
			return false;
		}
		if($currentdate>$expiredate)
			$this->deleteAttempts();
		return false;
	}
	private function addAttempt(){
		$ip = $this->getIp();
		FS::mkdir($this->attemptsPath);
		if(is_file($this->attemptsPath.$ip))
			$attempt_count = ((int)file_get_contents($this->attemptsPath.$ip))+1;
		else
			$attempt_count = 1;
		return file_put_contents($this->attemptsPath.$ip,$attempt_count,LOCK_EX);
	}
	private function deleteAttempts(){
		$ip = $this->getIp();
		return is_file($this->attemptsPath.$ip)&&unlink($this->attemptsPath.$ip);
	}
	public function getRandomKey($length = 40){
		$chars = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6";
		$key = "";
		for ($i = 0; $i < $length; $i++)
			$key .= $chars{mt_rand(0, strlen($chars) - 1)};
		return $key;
	}
	private function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
}