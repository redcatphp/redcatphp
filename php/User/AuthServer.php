<?php namespace Surikat\User;
use Surikat\User\Auth as User_Auth;
use Surikat\I18n\Lang;
use Surikat\DependencyInjection\MutatorMagic;
Lang::initialize();
class AuthServer{
	use MutatorMagic;
	protected $messages = [];
	protected $lastResult;
	protected $defaultLogoutKey = 'auth-server-logout';
	function __construct(User_Auth $Auth=null){
		if($Auth)
			$this->User_Auth = $Auth;
		$this->User_Session = $this->User_Auth->User_Session;
	}
	function getResultMessage($widget=false){
		if($this->lastResult&&!is_bool($this->lastResult)){
			return $this->getMessage($this->lastResult,$widget);
		}
	}
	function getResult(){
		return $this->lastResult;
	}
	function action($action=null){
		if(!func_num_args())
			$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
		if(!$action)
			return;
		$r = null;
		if(method_exists($this,$action)){
			$r = $this->$action();
			$ajax = $this->HTTP_Request->isAjax();
			if(!is_bool($r)){
				switch($r){
					case User_Auth::OK_LOGGED_IN:
						if(!$ajax){
							$this->User_Session->set('Auth','result',$action,$r);
							$this->HTTP_Request->reloadLocation();
						}
					break;
					case User_Auth::OK_REGISTER_SUCCESS:
						if(!$ajax){
							$this->User_Session->set('Auth','result',$action,$r);
							$this->HTTP_Request->reloadLocation();
						}
					break;
				}
			}
			if(!$r)
				$r = $this->User_Session->get('Auth','result',$action);
		}
		return $this->lastResult = $r;
	}
	function register(){
		if(isset($_POST['email'])&&isset($_POST['login'])&&isset($_POST['password'])&&isset($_POST['confirm'])){
			$email = $_POST['email'];
			$login = trim($_POST['login'])?$_POST['login']:$email;
			$this->User_Session->set('Auth','email',$email);
			return $this->User_Auth->register($email, $login, $_POST['password'], $_POST['confirm']);
		}
	}
	function resendactivate(){
		if($email=$this->User_Session->get('Auth','email')){
			return $this->User_Auth->resendActivation($email);
		}
	}
	function activate(){
		if(isset($_GET['key'])){
			return $this->User_Auth->activate($_GET['key']);
		}
	}
	function loginPersona(){
		if(isset($_POST['email'])&&$_POST['email']&&$_POST['email']==($email=$this->User_Session->get('email'))){
			$lifetime = 0;
			if(isset($_POST['login'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			return $this->User_Auth->loginPersona($email, $lifetime);
		}
	}
	function login(){
		if(isset($_POST['login'])&&isset($_POST['password'])){
			$lifetime = 0;
			if(isset($_POST['remember'])&&$_POST['remember']&&isset($_POST['lifetime'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			return $this->User_Auth->login($_POST['login'], $_POST['password'], $lifetime);
		}
		elseif(isset($_POST['email'])&&$_POST['email']){
			return $this->loginPersona();
		}
	}
	function resetreq(){
		if(isset($_POST['email'])){
			return $this->User_Auth->requestReset($_POST['email']);
		}
	}
	function resetpass(){
		if(isset($_GET['key'])&&isset($_POST['password'])&&isset($_POST['confirm'])){
			return $this->User_Auth->resetPass($_GET['key'], $_POST['password'], $_POST['confirm']);
		}
	}
	function lougoutAPI($key=null){
		if(!$key)
			$key = $this->defaultLogoutKey;
		if(isset($_POST[$key])){
			$this->logout();
			return true;
		}
	}
	function lougoutBTN($key=null,$ret=false){
		if(!$key)
			$key = $this->defaultLogoutKey;
		if($this->lougoutAPI()){
			$this->HTTP->reloadLocation();
		}
		else{
			$html = '
			<link href="'.$this->HTTP_URL->getBaseHref().'css/font/fontawesome.css" rel="stylesheet" type="text/css">
			<style type="text/css">
				a.auth-logout{
					background: none repeat scroll 0 0 #fff;
					border: 1px solid #000;
					border-radius: 3px;
					color: #000;
					padding: 1px 3px 0;
					position: absolute;
					right: 0;
					top: 0;
					z-index: 1000;
				}
				a,
				a:focus,
				a:hover,
				a:link:hover,
				a:visited:hover{
					color:#000;
					text-decoration:none;
				}
				a.auth-logout::before{
					font-family: FontAwesome;
					font-style: normal;
					font-size: 16px;
					font-weight: normal;
					line-height: normal;
					-webkit-font-smoothing: antialiased;
					speak: none;
					content: "\f011";
				}
			</style>
			<script type="text/javascript" src="'.$this->HTTP_URL->getBaseHref().'js/post.js"></script>
			<script type="text/javascript">
				authServerLogoutCaller = function(){
					post("'.$this->HTTP_URL->getLocation().'",{"'.$key.'":1});
					return false;
				};
			</script>
			';
			$html .= '<a class="auth-logout" onclick="return authServerLogoutCaller();" href="#"></a>';
			if($ret)
				return $html;
			else
				echo $html;
		}
	}
	function logout(){
		return $this->User_Auth->logout();
	}
	
	function htmlLock($r,$redirect=true){
		$action = $this->HTTP_URL->getLocation();
		if(isset($_POST['__login__'])&&isset($_POST['__password__'])){
			$lifetime = 0;
			if(isset($_POST['remember'])&&$_POST['remember']&&isset($_POST['lifetime'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			if($this->User_Auth->login($_POST['__login__'],$_POST['__password__'],$lifetime)===Auth::OK_LOGGED_IN){
				header('Location: '.$action,false,302);
				exit;
			}
		}
		if($this->User_Auth->allowed($r))
			return;
		if($this->User_Auth->connected()){
			if($redirect)
				header('Location: '.$this->User_Auth->siteUrl.'403',false,302);
			else
				$this->HTTP_Request->code(403);
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
		if($seconds=$this->User_Session->isBlocked()){
			echo $this->User_Auth->getMessage([Auth::ERROR_USER_BLOCKED,$seconds],true);
		}
		echo '<form id="form" action="'.$action.'" method="POST">
			<label for="__login__">Login</label><input type="text" id="__login__" name="__login__" placeholder="Login"><br>
			<label id="password" for="__password__">Password</label><input type="password" id="__password__" name="__password__" placeholder="Password"><br>
			<fieldset>
				<label for="remember">Remember me</label>
				<input type="checkbox" name="remember" value="1">
				<select name="lifetime">
					<option value="day">One Day</option>
					<option value="week" selected>One Week</option>
					<option value="month">One Month</option>
					<option value="year">One Year</option>
				</select>
			</fieldset>
			<input id="submit" value="Connection" type="submit">
		</form>
		</body></html>';
		exit;
	}
	
	
	function getMessage($code,$widget=false){
		$lg = Lang::currentLangCode();
		if(!isset($this->messages[$lg])){
			$this->messages[$lg] = [
				Auth::ERROR_USER_BLOCKED => __("Too many failed attempts, try again in %d seconds",null,'auth'),
				Auth::ERROR_USER_BLOCKED_2 => __("Too many failed attempts, try again in %d minutes and %d seconds",null,'auth'),
				Auth::ERROR_USER_BLOCKED_3 => __("Too many failed attempts, try again in :",null,'auth'),
				Auth::ERROR_LOGIN_SHORT => __("Login is too short",null,'auth'),
				Auth::ERROR_LOGIN_LONG => __("Login is too long",null,'auth'),
				Auth::ERROR_LOGIN_INCORRECT => __("Login is incorrect",null,'auth'),
				Auth::ERROR_LOGIN_INVALID => __("Login is invalid",null,'auth'),
				Auth::ERROR_NAME_INVALID => __("Name is invalid",null,'auth'),
				Auth::ERROR_PASSWORD_SHORT => __("Password is too short",null,'auth'),
				Auth::ERROR_PASSWORD_LONG => __("Password is too long",null,'auth'),
				Auth::ERROR_PASSWORD_INVALID => __("Password must contain at least one uppercase and lowercase character, and at least one digit",null,'auth'),
				Auth::ERROR_PASSWORD_NOMATCH => __("Passwords do not match",null,'auth'),
				Auth::ERROR_PASSWORD_INCORRECT => __("Current password is incorrect",null,'auth'),
				Auth::ERROR_PASSWORD_NOTVALID => __("Password is invalid",null,'auth'),
				Auth::ERROR_NEWPASSWORD_SHORT => __("New password is too short",null,'auth'),
				Auth::ERROR_NEWPASSWORD_LONG => __("New password is too long",null,'auth'),
				Auth::ERROR_NEWPASSWORD_INVALID => __("New password must contain at least one uppercase and lowercase character, and at least one digit",null,'auth'),
				Auth::ERROR_NEWPASSWORD_NOMATCH => __("New passwords do not match",null,'auth'),
				Auth::ERROR_LOGIN_PASSWORD_INVALID => __("Login / Password are invalid",null,'auth'),
				Auth::ERROR_LOGIN_PASSWORD_INCORRECT => __("Login / Password are incorrect",null,'auth'),
				Auth::ERROR_EMAIL_INVALID => __("Email address is invalid",null,'auth'),
				Auth::ERROR_EMAIL_INCORRECT => __("Email address is incorrect",null,'auth'),
				Auth::ERROR_NEWEMAIL_MATCH => __("New email matches previous email",null,'auth'),
				Auth::ERROR_ACCOUNT_INACTIVE => __("Account has not yet been activated",null,'auth'),
				Auth::ERROR_SYSTEM_ERROR => __("A system error has been encountered. Please try again",null,'auth'),
				Auth::ERROR_LOGIN_TAKEN => __("The login is already taken",null,'auth'),
				Auth::ERROR_EMAIL_TAKEN => __("The email address is already in use",null,'auth'),
				Auth::ERROR_AUTHENTICATION_REQUIRED => __("Authentication required",null,'auth'),
				Auth::ERROR_ALREADY_AUTHENTICATED => __("You are already authenticated",null,'auth'),
				Auth::ERROR_RESETKEY_INVALID => __("Reset key is invalid",null,'auth'),
				Auth::ERROR_RESETKEY_INCORRECT => __("Reset key is incorrect",null,'auth'),
				Auth::ERROR_RESETKEY_EXPIRED => __("Reset key has expired",null,'auth'),
				Auth::ERROR_ACTIVEKEY_INVALID => __("Activation key is invalid",null,'auth'),
				Auth::ERROR_ACTIVEKEY_INCORRECT => __("Activation key is incorrect",null,'auth'),
				Auth::ERROR_ACTIVEKEY_EXPIRED => __("Activation key has expired",null,'auth'),
				Auth::ERROR_RESET_EXISTS => __("A reset request already exists",null,'auth'),
				Auth::ERROR_ALREADY_ACTIVATED => __("Account is already activated",null,'auth'),
				Auth::ERROR_ACTIVATION_EXISTS => __("An activation email has already been sent",null,'auth'),
				
				Auth::OK_PASSWORD_CHANGED => __("Password changed successfully",null,'auth'),
				Auth::OK_EMAIL_CHANGED => __("Email address changed successfully",null,'auth'),
				Auth::OK_ACCOUNT_ACTIVATED => __("Account has been activated. You can now log in",null,'auth'),
				Auth::OK_ACCOUNT_DELETED => __("Account has been deleted",null,'auth'),
				Auth::OK_LOGGED_IN => __("You are now logged in",null,'auth'),
				Auth::OK_LOGGED_OUT => __("You are now logged out",null,'auth'),
				Auth::OK_REGISTER_SUCCESS => __("Account created. Activation email sent to email",null,'auth'),
				Auth::OK_PASSWORD_RESET => __("Password reset successfully",null,'auth'),
				Auth::OK_RESET_REQUESTED => __("Password reset request sent to email address",null,'auth'),
				Auth::OK_ACTIVATION_SENT => __("Activation email has been sent",null,'auth'),
			];
		}
		if(is_array($code)){
			$c = array_shift($code);
			switch($c){
				case Auth::ERROR_USER_BLOCKED:
					$t = array_shift($code);
					if($t>60){
						$c = Auth::ERROR_USER_BLOCKED_2;
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
						$r = '<div id="msgcountdown">'.$this->getMessage([Auth::ERROR_USER_BLOCKED,$t]).'</div>';
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
								msgcountdown.innerHTML = "'.$this->getMessage(Auth::ERROR_USER_BLOCKED_3).'";
								showCountDown();
								var interval = setInterval(showCountDown,1000);
							}
						</script>';
						return $r;
					}
				break;
			}
			array_unshift($code,$this->messages[$lg][$c]);
			return call_user_func_array('sprintf',$code);
		}
		else{
			return $this->messages[$lg][$code];
		}
	}
}